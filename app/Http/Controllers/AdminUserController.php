<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()
            ->withCount('pedidos')
            ->withMax('pedidos', 'created_at');

        if ($request->filled('q')) {
            $term = trim((string) $request->get('q'));
            $termLower = mb_strtolower($term);
            $digits = preg_replace('/\D/', '', $term);

            $query->where(function ($builder) use ($term, $termLower, $digits) {
                $builder
                    ->whereRaw('LOWER(name) LIKE ?', ['%'.$termLower.'%'])
                    ->orWhereRaw('LOWER(email) LIKE ?', ['%'.$termLower.'%']);

                if ($term !== '') {
                    $builder->orWhere('phone', 'like', '%'.$term.'%');
                }

                if ($digits !== '') {
                    $builder
                        ->orWhereRaw("regexp_replace(coalesce(phone, ''), '\\D', '', 'g') LIKE ?", ['%'.$digits.'%'])
                        ->orWhereRaw("regexp_replace(coalesce(cpf, ''), '\\D', '', 'g') LIKE ?", ['%'.$digits.'%']);
                }
            });
        }

        if ($request->filled('admin_filter')) {
            $query->where('admin', $request->get('admin_filter') === '1');
        }

        if ($request->filled('catalog_filter')) {
            if ($request->get('catalog_filter') === '1') {
                $query->where(function ($builder) {
                    $builder
                        ->where('admin', true)
                        ->orWhereJsonContains('admin_permissions', User::ADMIN_PERMISSION_CATALOG);
                });
            } elseif ($request->get('catalog_filter') === '0') {
                $query
                    ->where('admin', false)
                    ->where(function ($builder) {
                        $builder
                            ->whereNull('admin_permissions')
                            ->orWhereJsonDoesntContain('admin_permissions', User::ADMIN_PERMISSION_CATALOG);
                    });
            }
        }

        if ($request->filled('analytics_filter')) {
            if ($request->get('analytics_filter') === '1') {
                $query->where(function ($builder) {
                    $builder
                        ->where('admin', true)
                        ->orWhereJsonContains('admin_permissions', User::ADMIN_PERMISSION_ANALYTICS);
                });
            } elseif ($request->get('analytics_filter') === '0') {
                $query
                    ->where('admin', false)
                    ->where(function ($builder) {
                        $builder
                            ->whereNull('admin_permissions')
                            ->orWhereJsonDoesntContain('admin_permissions', User::ADMIN_PERMISSION_ANALYTICS);
                    });
            }
        }

        if ($request->filled('portal_filter')) {
            $query->where('coupon_portal_enabled', $request->get('portal_filter') === '1');
        }

        if ($request->filled('orders_filter')) {
            if ($request->get('orders_filter') === 'with_orders') {
                $query->has('pedidos');
            } elseif ($request->get('orders_filter') === 'without_orders') {
                $query->doesntHave('pedidos');
            }
        }

        $users = $query
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'total' => User::count(),
            'admins' => User::where('admin', true)->count(),
            'catalog_managers' => User::where(function ($builder) {
                $builder
                    ->where('admin', true)
                    ->orWhereJsonContains('admin_permissions', User::ADMIN_PERMISSION_CATALOG);
            })->count(),
            'analytics_managers' => User::where(function ($builder) {
                $builder
                    ->where('admin', true)
                    ->orWhereJsonContains('admin_permissions', User::ADMIN_PERMISSION_ANALYTICS);
            })->count(),
            'portal_enabled' => User::where('coupon_portal_enabled', true)->count(),
        ];

        return view('admin.usuarios', compact('users', 'summary'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        if ($request->filled('cpf')) {
            $request->merge([
                'cpf' => preg_replace('/\D/', '', (string) $request->cpf),
            ]);
        }

        if ($request->filled('phone')) {
            $phoneDigits = preg_replace('/\D/', '', (string) $request->phone);

            if (strlen($phoneDigits) < 10) {
                return back()
                    ->withErrors(['phone' => 'O telefone deve ter pelo menos 10 dígitos numéricos.'])
                    ->withInput()
                    ->with('user_modal_open', $user->id);
            }
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'phone' => [
                'nullable',
                'string',
                'regex:/^\(\d{2}\)\s\d{4,5}-\d{4}$/',
                Rule::unique('users', 'phone')->ignore($user->id),
            ],
            'cpf' => [
                'nullable',
                'string',
                'size:11',
                Rule::unique('users', 'cpf')->ignore($user->id),
            ],
            'admin' => ['nullable', 'boolean'],
            'catalog_manage' => ['nullable', 'boolean'],
            'analytics_view' => ['nullable', 'boolean'],
            'coupon_portal_enabled' => ['nullable', 'boolean'],
            'must_change_password' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'O nome é obrigatório.',
            'name.min' => 'O nome deve ter pelo menos 2 caracteres.',
            'phone.regex' => 'O telefone deve estar no formato (XX) XXXXX-XXXX ou (XX) XXXX-XXXX.',
            'phone.unique' => 'Este número de telefone já está sendo usado por outro usuário.',
            'cpf.size' => 'O CPF deve ter exatamente 11 dígitos.',
            'cpf.unique' => 'Este CPF já está cadastrado em outra conta.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('user_modal_open', $user->id);
        }

        $validated = $validator->validated();
        $adminPermissions = array_values(array_filter([
            $request->boolean('catalog_manage') ? User::ADMIN_PERMISSION_CATALOG : null,
            $request->boolean('analytics_view') ? User::ADMIN_PERMISSION_ANALYTICS : null,
        ]));

        $user->update([
            'name' => trim($validated['name']),
            'phone' => $this->emptyToNull($validated['phone'] ?? null),
            'cpf' => $this->emptyToNull($validated['cpf'] ?? null),
            'admin' => $request->boolean('admin'),
            'admin_permissions' => $adminPermissions === [] ? null : $adminPermissions,
            'coupon_portal_enabled' => $request->boolean('coupon_portal_enabled'),
            'must_change_password' => $request->boolean('must_change_password'),
        ]);

        return back()->with('success', 'Usuário atualizado com sucesso.');
    }

    private function emptyToNull(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : null;

        return $value === '' ? null : $value;
    }
}
