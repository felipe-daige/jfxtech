<?php

namespace Tests\Feature;

use App\Mail\SorteioParticipationConfirmedMail;
use App\Models\Produto;
use App\Models\ProdutoImagem;
use App\Models\Sorteio;
use App\Models\SorteioParticipante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SorteioTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_sorteio_page_renders_registration_form(): void
    {
        $produto = Produto::factory()->create([
            'nome' => 'Razer Viper V3 Pro',
            'preco' => 1199.99,
        ]);
        ProdutoImagem::create([
            'produto_id' => $produto->id,
            'caminho' => 'produtos/viper.png',
            'capa' => true,
            'ordem' => 1,
        ]);

        $sorteio = Sorteio::create([
            'titulo' => 'Setup Gamer',
            'slug' => 'setup-gamer',
            'premio' => 'Mousepad',
            'produto_id' => $produto->id,
            'ativo' => true,
            'numero_inicial' => 1,
        ]);

        $this->get(route('site.sorteio.show', $sorteio))
            ->assertOk()
            ->assertSee('Setup Gamer')
            ->assertSee('Razer Viper V3 Pro')
            ->assertSee(route('site.produto.detalhes', $produto->slug))
            ->assertSee('Gerar meu número');
    }

    public function test_guest_participation_creates_user_and_number(): void
    {
        Mail::fake();

        $sorteio = Sorteio::create([
            'titulo' => 'Setup Gamer',
            'slug' => 'setup-gamer',
            'premio' => 'Mousepad',
            'ativo' => true,
            'numero_inicial' => 100,
            'max_participantes' => 1,
        ]);

        $response = $this->post(route('site.sorteio.participar', $sorteio), [
            'name' => 'Lead Teste',
            'email' => 'lead@example.com',
            'phone' => '(11) 99999-9999',
            'cpf' => '529.982.247-25',
            'instagram_username' => '@leadteste',
            'instagram_friend_1' => '@amigo_um',
            'instagram_friend_2' => '@amigo_dois',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'instagram_requirements' => '1',
            'rules' => '1',
            'marketing_opt_in' => '1',
        ]);

        $response->assertRedirect(route('site.sorteio.acompanhar', $sorteio));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'lead@example.com',
            'phone' => '(11) 99999-9999',
            'cpf' => '52998224725',
        ]);

        $this->assertDatabaseHas('sorteio_participantes', [
            'sorteio_id' => $sorteio->id,
            'numero' => 100,
            'instagram_username' => 'leadteste',
            'instagram_friend_1' => 'amigo_um',
            'instagram_friend_2' => 'amigo_dois',
            'status' => SorteioParticipante::STATUS_PENDENTE,
        ]);

        Mail::assertQueued(SorteioParticipationConfirmedMail::class, function (SorteioParticipationConfirmedMail $mail) {
            $html = $mail->render();

            return $mail->hasTo('lead@example.com')
                && $mail->participacao->numeroFormatado() === '00100'
                && $mail->participacao->instagram_username === 'leadteste'
                && str_contains($html, '00100')
                && str_contains($html, '@leadteste');
        });
    }

    public function test_participation_number_uses_available_random_pool_instead_of_next_sequence(): void
    {
        $existingUser = User::factory()->create(['cpf' => '52998224725']);
        $sorteio = Sorteio::create([
            'titulo' => 'Setup Gamer',
            'slug' => 'setup-gamer',
            'premio' => 'Mousepad',
            'ativo' => true,
            'numero_inicial' => 100,
            'max_participantes' => 2,
        ]);

        SorteioParticipante::create([
            'sorteio_id' => $sorteio->id,
            'user_id' => $existingUser->id,
            'numero' => 101,
            'instagram_username' => 'participante_existente',
            'instagram_friend_1' => 'amigo1',
            'instagram_friend_2' => 'amigo2',
            'status' => SorteioParticipante::STATUS_PENDENTE,
        ]);

        Mail::fake();

        $this->post(route('site.sorteio.participar', $sorteio), [
            'name' => 'Lead Teste',
            'email' => 'lead@example.com',
            'phone' => '(11) 99999-9999',
            'cpf' => '390.533.447-05',
            'instagram_username' => '@leadteste',
            'instagram_friend_1' => '@amigo_um',
            'instagram_friend_2' => '@amigo_dois',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'instagram_requirements' => '1',
            'rules' => '1',
            'marketing_opt_in' => '1',
        ])->assertRedirect(route('site.sorteio.acompanhar', $sorteio));

        $this->assertDatabaseHas('sorteio_participantes', [
            'sorteio_id' => $sorteio->id,
            'numero' => 100,
            'instagram_username' => 'leadteste',
        ]);

        $this->assertDatabaseMissing('sorteio_participantes', [
            'sorteio_id' => $sorteio->id,
            'numero' => 102,
        ]);
    }

    public function test_logged_user_can_track_unpublished_result(): void
    {
        $user = User::factory()->create([
            'cpf' => '52998224725',
        ]);
        $sorteio = Sorteio::create([
            'titulo' => 'Setup Gamer',
            'slug' => 'setup-gamer',
            'ativo' => true,
            'numero_inicial' => 1,
        ]);

        SorteioParticipante::create([
            'sorteio_id' => $sorteio->id,
            'user_id' => $user->id,
            'numero' => 1,
            'instagram_username' => 'cliente',
            'instagram_friend_1' => 'amigo1',
            'instagram_friend_2' => 'amigo2',
            'status' => SorteioParticipante::STATUS_PENDENTE,
            'accepted_rules_at' => now(),
            'instagram_requirements_accepted_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('site.sorteio.acompanhar', $sorteio))
            ->assertOk()
            ->assertSee('00001')
            ->assertSee('Resultado ainda não publicado');
    }

    public function test_admin_can_publish_result_and_user_sees_winner(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $winner = User::factory()->create(['name' => 'Ganhador Teste', 'cpf' => '52998224725']);
        $other = User::factory()->create(['cpf' => '39053344705']);
        $sorteio = Sorteio::create([
            'titulo' => 'Setup Gamer',
            'slug' => 'setup-gamer',
            'ativo' => true,
            'numero_inicial' => 1,
        ]);

        $winnerParticipation = SorteioParticipante::create([
            'sorteio_id' => $sorteio->id,
            'user_id' => $winner->id,
            'numero' => 1,
            'instagram_username' => 'ganhador',
            'instagram_friend_1' => 'amigo1',
            'instagram_friend_2' => 'amigo2',
            'status' => SorteioParticipante::STATUS_PENDENTE,
        ]);

        SorteioParticipante::create([
            'sorteio_id' => $sorteio->id,
            'user_id' => $other->id,
            'numero' => 2,
            'instagram_username' => 'outro',
            'instagram_friend_1' => 'amigo3',
            'instagram_friend_2' => 'amigo4',
            'status' => SorteioParticipante::STATUS_PENDENTE,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.sorteios.resultado', $sorteio), [
                'ganhador_participante_id' => $winnerParticipation->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('sorteios', [
            'id' => $sorteio->id,
            'ganhador_participante_id' => $winnerParticipation->id,
        ]);

        $this->actingAs($other)
            ->get(route('site.sorteio.acompanhar', $sorteio))
            ->assertOk()
            ->assertSee('Resultado publicado')
            ->assertSee('Ganhador Teste')
            ->assertSee('00001');
    }

    public function test_published_result_is_visible_on_public_sorteio_page(): void
    {
        $winner = User::factory()->create(['name' => 'Ganhador Teste', 'cpf' => '52998224725']);
        $sorteio = Sorteio::create([
            'titulo' => 'Setup Gamer',
            'slug' => 'setup-gamer',
            'ativo' => true,
            'numero_inicial' => 1,
        ]);

        $winnerParticipation = SorteioParticipante::create([
            'sorteio_id' => $sorteio->id,
            'user_id' => $winner->id,
            'numero' => 1,
            'instagram_username' => 'ganhador',
            'instagram_friend_1' => 'amigo1',
            'instagram_friend_2' => 'amigo2',
            'status' => SorteioParticipante::STATUS_VALIDADO,
        ]);

        $sorteio->update([
            'ganhador_participante_id' => $winnerParticipation->id,
            'resultado_publicado_at' => now(),
        ]);

        $this->get(route('site.sorteio.show', $sorteio))
            ->assertOk()
            ->assertSee('Resultado publicado')
            ->assertSee('O resultado final deste sorteio já está disponível para todos.')
            ->assertSee('Ganhador Teste')
            ->assertSee('@ganhador')
            ->assertSee('00001')
            ->assertDontSee('Gerar meu número');
    }

    public function test_admin_can_manage_sorteios_page(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $produto = Produto::factory()->create([
            'nome' => 'Razer Viper V3 Pro',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.sorteios.store'), [
                'titulo' => 'Sorteio Maio',
                'slug' => 'sorteio-maio',
                'premio' => 'Teclado',
                'produto_id' => $produto->id,
                'instagram_post_url' => 'https://www.instagram.com/p/teste/',
                'numero_inicial' => 500,
                'ativo' => '1',
            ])
            ->assertRedirect(route('admin.sorteios.index'));

        $this->assertDatabaseHas('sorteios', [
            'titulo' => 'Sorteio Maio',
            'slug' => 'sorteio-maio',
            'premio' => 'Teclado',
            'produto_id' => $produto->id,
            'numero_inicial' => 500,
            'ativo' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.sorteios.index'))
            ->assertOk()
            ->assertSee('Sorteio Maio')
            ->assertSee('Razer Viper V3 Pro')
            ->assertSee('Ver rota do usuario');
    }

    public function test_admin_can_view_sorteio_participants_page(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $user = User::factory()->create([
            'name' => 'Cliente Sorteio',
            'email' => 'cliente@example.com',
            'cpf' => '52998224725',
        ]);
        $sorteio = Sorteio::create([
            'titulo' => 'Setup Gamer',
            'slug' => 'setup-gamer',
            'ativo' => true,
            'numero_inicial' => 1,
        ]);

        SorteioParticipante::create([
            'sorteio_id' => $sorteio->id,
            'user_id' => $user->id,
            'numero' => 1,
            'instagram_username' => 'cliente',
            'instagram_friend_1' => 'amigo1',
            'instagram_friend_2' => 'amigo2',
            'status' => SorteioParticipante::STATUS_PENDENTE,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.sorteios.show', $sorteio))
            ->assertOk()
            ->assertSee('Cliente Sorteio')
            ->assertSee('Publicar resultado final');
    }
}
