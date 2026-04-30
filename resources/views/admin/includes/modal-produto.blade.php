<!-- Modal de Produto -->
<div id="modalProduto" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-2 sm:p-4">
        <div class="bg-white border border-[var(--color-lab-border)] w-full max-w-4xl max-h-[95vh] sm:max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="p-4 sm:p-6 border-b border-[var(--color-lab-border)]">
                <div class="flex justify-between items-center">
                    <h3 class="font-mono text-sm font-bold uppercase tracking-widest text-black" id="modalTitulo">Novo Produto</h3>
                    <button onclick="fecharModalProduto()" class="text-[var(--color-lab-muted)] hover:text-black p-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
            </div>

            <form id="formProduto" method="POST" enctype="multipart/form-data">
                @csrf
                <div id="formMethod" style="display: none;"></div>
                <input type="hidden" id="produtoId" name="produto_id" value="">

                <div class="px-4 sm:px-6 pt-4">
                    {{-- Tab navigation --}}
                    <div class="flex overflow-x-auto admin-mobile-scroll border-b border-gray-200 mb-6" id="produto-modal-tabs">
                        <button type="button"
                                onclick="switchProdutoTab('dados')"
                                class="produto-tab-btn shrink-0 px-4 py-2 text-sm font-mono uppercase tracking-widest border-b-2 -mb-px transition-colors border-black text-black"
                                data-tab="dados">
                            DADOS
                        </button>
                        <button type="button"
                                onclick="switchProdutoTab('imagens')"
                                class="produto-tab-btn shrink-0 px-4 py-2 text-sm font-mono uppercase tracking-widest border-b-2 -mb-px transition-colors border-transparent text-gray-400 hover:text-black"
                                data-tab="imagens">
                            IMAGENS
                        </button>
                        <button type="button"
                                onclick="switchProdutoTab('variantes')"
                                class="produto-tab-btn shrink-0 px-4 py-2 text-sm font-mono uppercase tracking-widest border-b-2 -mb-px transition-colors border-transparent text-gray-400 hover:text-black"
                                data-tab="variantes">
                            VARIANTES
                        </button>
                        <button type="button"
                                onclick="switchProdutoTab('fornecedores')"
                                class="produto-tab-btn shrink-0 px-4 py-2 text-sm font-mono uppercase tracking-widest border-b-2 -mb-px transition-colors border-transparent text-gray-400 hover:text-black"
                                data-tab="fornecedores">
                            FORNECEDORES
                        </button>
                        <button type="button"
                                onclick="switchProdutoTab('specs')"
                                class="produto-tab-btn shrink-0 px-4 py-2 text-sm font-mono uppercase tracking-widest border-b-2 -mb-px transition-colors border-transparent text-gray-400 hover:text-black"
                                data-tab="specs">
                            SPECS
                        </button>
                    </div>
                </div>

                {{-- Tab panels --}}
                <div id="tab-dados" class="produto-tab-panel">
                <div id="formFields" class="px-4 sm:px-6 pb-6 space-y-5">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Nome do Produto</label>
                            <input type="text" name="nome" id="nome" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" required>
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Marca</label>
                            <input type="text" name="marca" id="marca" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" maxlength="100" placeholder="ex: Logitech">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Categoria</label>
                            <select name="categoria_id" id="categoria_id" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black bg-white" required>
                                <option value="">Selecione uma categoria</option>
                                @foreach($categorias as $categoria)
                                    <option value="{{ $categoria->id }}">{{ $categoria->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Descrição Curta</label>
                            <textarea name="descricao_curta" id="descricao_curta" rows="4" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="Resumo curto para card, SEO e metadados"></textarea>
                        </div>
                    </div>

                    <div>
                        <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Descricao</label>
                        <p class="text-xs text-[var(--color-lab-muted)] leading-6 mb-3">
                            Use HTML simples para destacar a leitura: <code>&lt;p&gt;</code>, <code>&lt;strong&gt;</code>, <code>&lt;ul&gt;</code> e <code>&lt;li&gt;</code>.
                            Exemplo: um parágrafo curto, um bloco com <strong>Principais destaques</strong> e uma lista de benefícios.
                        </p>
                        <textarea name="descricao" id="descricao" rows="12" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" required></textarea>
                        <div class="admin-description-preview mt-4 border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)]">
                            <div class="border-b border-[var(--color-lab-border)] px-4 py-3 flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Prévia da descrição</p>
                                    <p class="text-xs text-[var(--color-lab-muted)] mt-1">A prévia renderiza o HTML permitido antes de salvar.</p>
                                </div>
                                <span id="descricaoPreviewStatus" class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Aguardando texto</span>
                            </div>
                            <div id="descricaoPreview" class="p-4 sm:p-5">
                                <div class="product-description-content text-sm text-gray-500">
                                    <p>Digite a descrição usando HTML simples para ver a renderização aqui.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Preco (R$)</label>
                            <input type="text" name="preco" id="preco" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="0,00" required>
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Custo de Compra (R$)</label>
                            <input type="text" name="custo_compra" id="custo_compra" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="0,00">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Frete Compra (R$)</label>
                            <input type="text" name="frete_compra" id="frete_compra" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="0,00">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Estoque</label>
                            <input type="number" name="estoque" id="estoque" min="0" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" required>
                        </div>
                        <div id="resumoFinanceiroProduto" class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] p-4">
                            <div class="font-mono text-sm text-black space-y-1">
                                <div class="flex justify-between">
                                    <span class="text-[var(--color-lab-muted)]">Venda efetiva:</span>
                                    <span id="precoVendaResumoDisplay">R$ 0,00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[var(--color-lab-muted)]">Custo compra:</span>
                                    <span id="custoCompraDisplay">R$ 0,00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[var(--color-lab-muted)]">Frete compra:</span>
                                    <span id="freteCompraDisplay">R$ 0,00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[var(--color-lab-muted)]">Custo total:</span>
                                    <span id="custoTotalCompraDisplay">R$ 0,00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[var(--color-lab-muted)]">Lucro unitario:</span>
                                    <span id="lucroUnitarioDisplay">R$ 0,00</span>
                                </div>
                                <div class="flex justify-between font-bold text-base border-t border-[var(--color-lab-border)] pt-2 mt-2">
                                    <span>Margem bruta:</span>
                                    <span id="margemBrutaDisplay">0,00%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Secao de Promocao -->
                    <div>
                        <div class="flex items-center mb-4">
                            <input type="hidden" name="em_promocao" value="0">
                            <input type="checkbox" name="em_promocao" id="em_promocao" value="1" class="h-4 w-4 border-[var(--color-lab-border)] text-black focus:ring-black">
                            <label for="em_promocao" class="ml-2 font-mono text-xs text-black">Produto em promocao</label>
                        </div>

                        <div id="camposPromocao" class="hidden">
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Desconto (%)</label>
                                    <input type="number" name="desconto_percentual" id="desconto_percentual" min="0" max="100" step="0.01" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="0">
                                </div>
                            </div>
                        </div>

                        <div id="resumoPromocao" class="mt-3 p-4 border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] hidden">
                            <div class="font-mono text-sm text-black space-y-1">
                                <div class="flex justify-between">
                                    <span class="text-[var(--color-lab-muted)]">Preco original:</span>
                                    <span id="precoOriginalDisplay">R$ 0,00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[var(--color-lab-muted)]">Desconto:</span>
                                    <span id="descontoDisplay">R$ 0,00</span>
                                </div>
                                <div class="flex justify-between font-bold text-base border-t border-[var(--color-lab-border)] pt-2 mt-2">
                                    <span>Preco com desconto:</span>
                                    <span id="precoFinalDisplay">R$ 0,00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Secao de Destaque e Tags -->
                    <div>
                        <div class="flex items-center mb-4">
                            <input type="hidden" name="destaque" value="0">
                            <input type="checkbox" name="destaque" id="destaque" value="1" class="h-4 w-4 border-[var(--color-lab-border)] text-black focus:ring-black">
                            <label for="destaque" class="ml-2 font-mono text-xs text-black">Produto em destaque (maximo 3)</label>
                        </div>

                        <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Tags do Produto (Máx. 2)</label>
                        <div class="space-y-2 mt-2" id="tagsSelectionGroup">
                            <div class="flex items-center">
                                <input type="checkbox" name="tags[]" id="tag_exclusivo" value="Exclusivo" class="tag-checkbox h-4 w-4 border-[var(--color-lab-border)] text-black focus:ring-black">
                                <label for="tag_exclusivo" class="ml-2 font-mono text-xs text-black">Exclusivo</label>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" name="tags[]" id="tag_em_breve" value="Em Breve" class="tag-checkbox h-4 w-4 border-[var(--color-lab-border)] text-black focus:ring-black">
                                <label for="tag_em_breve" class="ml-2 font-mono text-xs text-black">Em Breve</label>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" name="tags[]" id="tag_lancamento" value="Lançamento" class="tag-checkbox h-4 w-4 border-[var(--color-lab-border)] text-black focus:ring-black">
                                <label for="tag_lancamento" class="ml-2 font-mono text-xs text-black">Lançamento</label>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" name="tags[]" id="tag_oferta" value="Oferta Especial" class="tag-checkbox h-4 w-4 border-[var(--color-lab-border)] text-black focus:ring-black">
                                <label for="tag_oferta" class="ml-2 font-mono text-xs text-black">Oferta Especial</label>
                            </div>
                        </div>
                        <p id="tags_warning" class="hidden mt-1 font-mono text-[10px] text-red-500">Você só pode selecionar até 2 tags.</p>
                    </div>

                </div>
                </div>{{-- end #tab-dados --}}

                <div id="tab-imagens" class="produto-tab-panel hidden">
                <div class="px-4 sm:px-6 pb-6 space-y-5">
                    <div>
                        <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Imagens</label>
                        <input type="file" name="imagens[]" id="imagens" multiple accept="image/*" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black file:mr-3 file:mb-2 sm:file:mb-0 file:py-1 file:px-3 file:border-0 file:text-xs file:font-mono file:font-bold file:uppercase file:tracking-widest file:bg-black file:text-white">
                        <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-1">Selecione uma ou mais imagens (maximo 2MB cada)</p>
                        <div id="novasImagensPreview" class="hidden mt-3 grid grid-cols-2 sm:grid-cols-3 gap-2"></div>
                    </div>
                </div>
                </div>{{-- end #tab-imagens --}}

                <div id="tab-variantes" class="produto-tab-panel hidden">
                <div class="px-4 sm:px-6 pb-6">
                    <div id="variantes-loading" class="text-center py-8 text-gray-400 font-mono text-sm">CARREGANDO...</div>
                    <div id="variantes-content" class="hidden">

                        {{-- Estoque compartilhado --}}
                        <div class="flex items-start gap-3 mb-6 p-4 border border-gray-200">
                            <input type="checkbox" id="estoque-compartilhado" class="w-4 h-4">
                            <label for="estoque-compartilhado" class="text-xs sm:text-sm font-mono uppercase tracking-widest leading-5">
                                Compartilhar estoque entre variantes
                            </label>
                        </div>

                        {{-- Grupos e valores --}}
                        <div id="grupos-container" class="space-y-4 mb-6"></div>
                        <button type="button" id="add-grupo-btn"
                                onclick="addGrupoOpcoes()"
                                class="text-sm font-mono uppercase tracking-widest border border-dashed border-gray-400 px-4 py-2 hover:border-black transition-colors w-full mb-6">
                            + ADICIONAR GRUPO DE OPÇÕES
                        </button>

                        {{-- Tabela de variantes --}}
                        <div id="variantes-tabela-container" class="hidden">
                            <h4 class="font-mono text-xs uppercase tracking-widest text-gray-500 mb-3">VARIANTES GERADAS</h4>
                            <div id="variantes-tabela" class="space-y-2"></div>
                        </div>

                        {{-- Botões salvar + recarregar --}}
                        <div class="mt-4">
                            <button type="button" id="salvar-btn"
                                    onclick="salvarTudo(window.currentProdutoId)"
                                    class="w-full bg-black text-white font-mono uppercase tracking-widest text-sm px-6 py-3 hover:bg-gray-800 transition-colors">
                                SALVAR
                            </button>
                        </div>
                    </div>
                </div>
                </div>{{-- end #tab-variantes --}}

                <div id="tab-fornecedores" class="produto-tab-panel hidden">
                <div class="px-4 sm:px-6 pb-6 space-y-4">
                    <div id="fornecedores-loading" class="text-center py-8 text-gray-400 font-mono text-sm">CARREGANDO...</div>
                    <div id="fornecedores-content" class="hidden space-y-4">
                        <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] p-4">
                            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Fornecedores do produto</p>
                            <p class="mt-1 text-xs text-[var(--color-lab-muted)] leading-5">Cadastre contatos, links e preços individuais de compra de cada fornecedor. Linhas vazias são ignoradas.</p>
                        </div>

                        <div id="fornecedores-ofertas-lista" class="space-y-3"></div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <button type="button"
                                    onclick="addFornecedorOfertaRow()"
                                    class="w-full border border-dashed border-gray-400 px-4 py-3 text-sm font-mono uppercase tracking-widest hover:border-black transition-colors">
                                + Adicionar fornecedor
                            </button>
                            <button type="button"
                                    onclick="salvarFornecedoresProduto(window.currentProdutoId)"
                                    class="w-full bg-black text-white px-4 py-3 text-sm font-mono uppercase tracking-widest hover:bg-gray-800 transition-colors">
                                Salvar fornecedores
                            </button>
                        </div>
                    </div>
                </div>
                </div>{{-- end #tab-fornecedores --}}

                <div id="tab-specs" class="produto-tab-panel hidden">
                <div class="px-4 sm:px-6 pb-6 space-y-5">
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Especificações técnicas — deixe em branco os campos não aplicáveis</p>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Sensor</label>
                            <input type="text" name="specs[sensor]" id="spec_sensor" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: Pulsar XS-1 Optical">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">DPI Máximo</label>
                            <input type="text" name="specs[dpi_maximo]" id="spec_dpi_maximo" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: 32.000 DPI">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Switches</label>
                            <input type="text" name="specs[switches]" id="spec_switches" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: Óptico Pulsar (100M cliques)">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Peso</label>
                            <input type="text" name="specs[peso]" id="spec_peso" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: 43 g">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Conexão</label>
                            <input type="text" name="specs[conexao]" id="spec_conexao" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: 2,4 GHz Wireless + USB-C">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Polling Rate</label>
                            <input type="text" name="specs[polling_rate]" id="spec_polling_rate" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: 1.000 Hz">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Dimensões</label>
                            <input type="text" name="specs[dimensoes]" id="spec_dimensoes" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: 119,6 × 67,1 × 41 mm">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Cabo</label>
                            <input type="text" name="specs[cabo]" id="spec_cabo" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: USB-C 1,8 m paracord">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Iluminação</label>
                            <input type="text" name="specs[iluminacao]" id="spec_iluminacao" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: RGB / Sem RGB">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Garantia</label>
                            <input type="text" name="specs[garantia]" id="spec_garantia" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: 1 ano">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Layout</label>
                            <input type="text" name="specs[layout]" id="spec_layout" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: TKL, 60%, 65%, 75%">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Superfície</label>
                            <input type="text" name="specs[superficie]" id="spec_superficie" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: Cloth — Alta Controle">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Base</label>
                            <input type="text" name="specs[base]" id="spec_base" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: Borracha antiderrapante">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Drivers</label>
                            <input type="text" name="specs[drivers]" id="spec_drivers" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: 40mm Neodymium">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Frequência</label>
                            <input type="text" name="specs[frequencia]" id="spec_frequencia" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: 20 Hz – 20.000 Hz">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Microfone</label>
                            <input type="text" name="specs[microfone]" id="spec_microfone" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: Retrátil bidirecional">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Bateria</label>
                            <input type="text" name="specs[bateria]" id="spec_bateria" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: 22 horas">
                        </div>
                    </div>
                </div>
                </div>{{-- end #tab-specs --}}

                <div class="p-4 sm:p-6 border-t border-[var(--color-lab-border)] flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
                    <button type="button" onclick="fecharModalProduto()" class="w-full sm:w-auto px-5 py-2.5 text-xs font-bold tracking-widest uppercase border border-[var(--color-lab-border)] text-black hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="w-full sm:w-auto bg-black text-white px-5 py-2.5 text-xs font-bold tracking-widest uppercase hover:bg-gray-800 transition-colors">
                        Salvar Produto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Lightbox de imagem -->
<div id="lightboxImagem" class="fixed inset-0 bg-black/80 hidden z-[60] flex items-center justify-center p-4" onclick="fecharLightbox()">
    <button onclick="fecharLightbox()" class="absolute top-4 right-4 text-white text-2xl font-mono leading-none hover:text-gray-300">✕</button>
    <img id="lightboxImg" src="" alt="" class="max-h-[90vh] max-w-[90vw] object-contain" onclick="event.stopPropagation()">
</div>
