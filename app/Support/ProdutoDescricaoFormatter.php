<?php

namespace App\Support;

use App\Models\Produto;

class ProdutoDescricaoFormatter
{
    /**
     * Map de labels amigáveis para specs conhecidas.
     *
     * @var array<string, string>
     */
    protected static array $specLabels = [
        'sensor' => 'Sensor',
        'dpi_maximo' => 'DPI Máximo',
        'switches' => 'Switches',
        'peso' => 'Peso',
        'conexao' => 'Conexão',
        'polling_rate' => 'Polling Rate',
        'dimensoes' => 'Dimensões',
        'cabo' => 'Cabo',
        'iluminacao' => 'Iluminação',
        'garantia' => 'Garantia',
        'layout' => 'Layout',
        'superficie' => 'Superfície',
        'base' => 'Base',
        'drivers' => 'Drivers',
        'frequencia' => 'Frequência',
        'microfone' => 'Microfone',
        'bateria' => 'Bateria',
    ];

    /**
     * Ordem preferencial de specs por categoria.
     *
     * @var array<string, array<int, string>>
     */
    protected static array $preferredSpecsByCategory = [
        'Monitores' => ['conexao', 'garantia', 'dimensoes', 'peso'],
        'Teclados' => ['switches', 'layout', 'conexao', 'iluminacao', 'cabo'],
        'Mouses' => ['sensor', 'dpi_maximo', 'peso', 'polling_rate', 'conexao'],
        'Mousepads' => ['superficie', 'base', 'dimensoes'],
        'Fones de Ouvido' => ['conexao', 'frequencia', 'microfone', 'bateria', 'drivers'],
    ];

    /**
     * Frases curtas por categoria para completar a lista quando faltarem bullets.
     *
     * @var array<string, array<int, string>>
     */
    protected static array $fallbackHighlightsByCategory = [
        'Monitores' => [
            'Leitura mais rápida de alvos, movimentação e microajustes em partidas competitivas.',
            'Pensado para setups que exigem consistência visual e resposta imediata.',
        ],
        'Teclados' => [
            'Resposta rápida e controle refinado para jogos competitivos e uso intenso no dia a dia.',
            'Formato pensado para liberar espaço de mouse e manter a mesa mais limpa.',
        ],
        'Mouses' => [
            'Precisão alta para flicks, tracking e microcorreções em jogos competitivos.',
            'Construção voltada para sessões longas com controle e conforto.',
        ],
        'Mousepads' => [
            'Base estável para movimentos consistentes e melhor controle do mouse.',
            'Equilíbrio entre deslizamento previsível e sensação de segurança no tracking.',
        ],
        'Fones de Ouvido' => [
            'Entrega conforto e imersão para longas sessões de jogo, trabalho ou música.',
            'Projeto pensado para comunicação clara e uso intenso em setup gamer.',
        ],
    ];

    public static function sanitize(?string $html): string
    {
        $html = str_replace(["\r\n", "\r"], "\n", trim((string) $html));

        if ($html === '') {
            return '';
        }

        $html = str_replace('&nbsp;', ' ', $html);
        $html = strip_tags($html, '<p><ul><ol><li><strong><em><br>');
        $html = preg_replace_callback('/<(\/?)(p|ul|ol|li|strong|em|br)\b[^>]*>/i', static function (array $matches): string {
            $tag = strtolower($matches[2]);

            if ($tag === 'br') {
                return '<br>';
            }

            return $matches[1] === '/' ? "</{$tag}>" : "<{$tag}>";
        }, $html) ?? $html;
        $html = preg_replace("/\n{3,}/", "\n\n", $html) ?? $html;
        $html = preg_replace('/<p>\s*<\/p>/i', '', $html) ?? $html;
        $html = preg_replace('/<li>\s*<\/li>/i', '', $html) ?? $html;

        do {
            $previous = $html;
            $html = preg_replace('/<(ul|ol)>\s*<\/\1>/i', '', $html) ?? $html;
        } while ($html !== $previous);

        return trim($html);
    }

    public static function toPlainText(?string $html): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $html)));

        return $text;
    }

    public static function formatProduto(Produto $produto): string
    {
        $plainText = self::toPlainText($produto->descricao);

        if ($plainText === '') {
            return '';
        }

        $sentences = self::splitSentences($plainText);
        $introCount = self::determineIntroSentenceCount($sentences);
        $intro = implode(' ', array_slice($sentences, 0, $introCount));
        $contextSentences = array_slice($sentences, $introCount, 2);
        $context = implode(' ', $contextSentences);
        $remainingSentences = array_values(array_slice($sentences, $introCount + count($contextSentences)));

        $parts = [];
        $parts[] = '<p>' . self::highlightText($intro) . '</p>';

        if ($context !== '') {
            $parts[] = '<p>' . self::highlightText($context) . '</p>';
        }

        $highlights = self::buildHighlights($produto, $remainingSentences);
        if ($highlights !== []) {
            $parts[] = '<p><strong>Principais destaques</strong></p>';
            $parts[] = '<ul><li>' . implode('</li><li>', $highlights) . '</li></ul>';
        }

        $closing = self::buildClosingParagraph($produto, $remainingSentences);
        if ($closing !== '') {
            $parts[] = '<p>' . self::highlightText($closing) . '</p>';
        }

        return self::sanitize(implode("\n", $parts));
    }

    /**
     * @return array<int, string>
     */
    protected static function splitSentences(string $text): array
    {
        $parts = preg_split('/(?<=[.!?])\s+/u', trim($text)) ?: [];

        return array_values(array_filter(array_map(
            static fn (string $part): string => trim($part),
            $parts
        )));
    }

    /**
     * @param array<int, string> $sentences
     */
    protected static function determineIntroSentenceCount(array $sentences): int
    {
        if ($sentences === []) {
            return 0;
        }

        if (count($sentences) === 1) {
            return 1;
        }

        return mb_strlen($sentences[0]) <= 180 ? 2 : 1;
    }

    /**
     * @param array<int, string> $remainingSentences
     * @return array<int, string>
     */
    protected static function buildHighlights(Produto $produto, array $remainingSentences): array
    {
        $items = [];
        $specs = is_array($produto->specs) ? array_filter($produto->specs, static fn ($value) => $value !== null && $value !== '') : [];
        $categoryName = $produto->categoria->nome ?? '';
        $preferredKeys = array_values(array_unique(array_merge(
            self::$preferredSpecsByCategory[$categoryName] ?? [],
            array_keys($specs)
        )));

        foreach ($preferredKeys as $key) {
            if (!array_key_exists($key, $specs)) {
                continue;
            }

            $label = self::$specLabels[$key] ?? ucfirst(str_replace('_', ' ', $key));
            $items[] = '<strong>' . self::escape($label) . ':</strong> ' . self::highlightText((string) $specs[$key]);

            if (count($items) >= 4) {
                break;
            }
        }

        foreach ($remainingSentences as $sentence) {
            if (count($items) >= 5) {
                break;
            }

            if (mb_strlen($sentence) < 45) {
                continue;
            }

            $items[] = self::highlightText($sentence);
        }

        foreach (self::$fallbackHighlightsByCategory[$categoryName] ?? [] as $fallback) {
            if (count($items) >= 5) {
                break;
            }

            $items[] = self::highlightText($fallback);
        }

        return array_slice(array_values(array_unique($items)), 0, max(3, min(5, count($items))));
    }

    /**
     * @param array<int, string> $remainingSentences
     */
    protected static function buildClosingParagraph(Produto $produto, array $remainingSentences): string
    {
        $categoryName = $produto->categoria->nome ?? '';

        if ($remainingSentences !== []) {
            $closing = implode(' ', array_slice($remainingSentences, 0, 2));
            if (mb_strlen($closing) >= 70) {
                return $closing;
            }
        }

        return match ($categoryName) {
            'Monitores' => 'É uma opção forte para quem busca vantagem competitiva, imagem estável e sensação de resposta imediata em partidas de alto nível.',
            'Teclados' => 'É indicado para quem quer um teclado mais ágil, organizado na mesa e pronto para extrair performance real em jogos competitivos.',
            'Mouses' => 'É uma escolha sólida para jogadores que querem movimentos precisos, resposta rápida e controle consistente em sessões intensas.',
            'Mousepads' => 'Fecha bem setups que exigem deslize previsível, estabilidade na base e leitura consistente em movimentos rápidos ou controlados.',
            'Fones de Ouvido' => 'Entrega uma experiência equilibrada para quem valoriza imersão, conforto e comunicação clara durante longas sessões.',
            default => 'É uma escolha pensada para quem quer desempenho consistente, leitura rápida dos diferenciais e uma experiência mais refinada no setup.',
        };
    }

    protected static function highlightText(string $text): string
    {
        $escaped = self::escape(trim($text));

        if ($escaped === '') {
            return '';
        }

        $patterns = [
            '/\b(\d+(?:[.,]\d+)?\s?(?:Hz|ms|%|g|kg|mm|cm|mAh|DPI|dpi|kHz|GHz))\b/u',
            '/\b(\d+(?:[.,]\d+)?")/u',
            '/\b(QD-OLED|OLED|Fast IPS|IPS|Fast TN|TN|Wireless|USB-C|RGB|Bluetooth|FreeSync Premium(?: Pro)?|G-SYNC|DyAc(?:™)?(?:\s?2)?|Rapid Trigger|Hall Effect|Lekker|2\.4\s?GHz|polling rate|esports|FPS)\b/iu',
        ];

        foreach ($patterns as $pattern) {
            $escaped = preg_replace($pattern, '<strong>$1</strong>', $escaped) ?? $escaped;
        }

        return $escaped;
    }

    protected static function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
