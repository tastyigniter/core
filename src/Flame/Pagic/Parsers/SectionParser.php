<?php

namespace Igniter\Flame\Pagic\Parsers;

use Symfony\Component\Yaml\Yaml;

class SectionParser
{
    const SOURCE_SEPARATOR = '---';

    /**
     * Parses a page or layout file content.
     * The expected file format is following:
     * <pre>
     * ---
     * Data (frontmatter) section
     * ---
     * PHP code section
     * ---
     * Html markup section
     * </pre>
     * If the content has only 2 sections they are considered as Data and Html.
     * If there is only a single section, it is considered as Html.
     *
     * @param string $content The file content.
     *
     * @return array Returns an array with the following indexes: 'data', 'markup', 'code'.
     * The 'markup' and 'code' elements contain strings. The 'settings' element contains the
     * parsed Data as array. If the content string does not contain a section, the corresponding
     * result element has null value.
     */
    public static function parse($content)
    {
        $separator = static::SOURCE_SEPARATOR;

        // Split the document into three sections.
        $doc = explode($separator, $content);

        $count = count($doc);

        $result = [
            'settings' => null,
            'code' => null,
            'markup' => null,
        ];

        // Data, code and markup
        if ($count === 4) {
            $frontMatter = trim($doc[1]);
            $result['settings'] = Yaml::parse($frontMatter);
            $result['code'] = trim($doc[2]);
            $result['markup'] = $doc[3];
        }
        // Data and markup
        elseif ($count === 3) {
            $frontMatter = trim($doc[1]);
            $result['settings'] = Yaml::parse($frontMatter);
            $result['markup'] = $doc[2];
        }
        // Only markup
        elseif ($count === 2) {
            $result['code'] = trim($doc[0]);
            $result['markup'] = $doc[1];
        }
        // Only markup, no separator
        elseif ($count === 1 && !is_null($content)) {
            $result['markup'] = $doc[0];
        }

        return $result;
    }

    /**
     * Renders a page or layout object as file content.
     *
     * @return string
     */
    public static function render($data)
    {
        $code = trim(array_get($data, 'code'));
        $markup = trim(array_get($data, 'markup'));
        $settings = array_get($data, 'settings', []);

        // Build content
        $content = [];

        if ($settings) {
            $content[] = self::SOURCE_SEPARATOR.PHP_EOL.trim(Yaml::dump($settings), PHP_EOL);
        }

        if ($code) {
            $code = preg_replace('/^\<\?php/', '', $code);
            $code = preg_replace('/^\<\?/', '', preg_replace('/\?>$/', '', $code));

            $code = trim($code, PHP_EOL);
            $content[] = '<?php'.PHP_EOL.$code.PHP_EOL.'?>';
        }

        $content[] = $markup;

        return trim(implode(PHP_EOL.self::SOURCE_SEPARATOR.PHP_EOL, $content));
    }
}
