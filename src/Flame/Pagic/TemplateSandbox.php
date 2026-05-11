<?php

declare(strict_types=1);

namespace Igniter\Flame\Pagic;

class TemplateSandbox
{
    public function sanitize(string $template): string
    {
        // Order matters - most dangerous first
        $template = $this->removeNullBytes($template);
        $template = $this->removePhpTags($template);
        $template = $this->removePhpBlocks($template);
        $template = $this->removeUnsafeBladeDirectives($template);
        $template = $this->removeUnescapedOutput($template);
        $template = $this->removeObfuscation($template);
        $template = $this->removeVariableVariables($template);

        return $this->removePathTraversal($template);
    }

    protected function removeNullBytes(string $content): string
    {
        return str_replace("\0", '', $content);
    }

    protected function removePhpTags(string $content): string
    {
        // With closing tag
        $content = preg_replace('/<\?(?:php|=).*?\?>/si', '', $content) ?? $content;

        // Without closing tag - rest of content after opening tag
        $content = preg_replace('/<\?(?:php|=)[\s\S]*$/i', '', $content) ?? $content;

        // Short open tags (but preserve <?xml)
        $content = preg_replace('/<\?(?!xml)[\s\S]*$/i', '', $content) ?? $content;

        // Catch any remnants
        $content = str_replace(['<?', '?>'], '', $content);

        return $content;
    }

    protected function removePhpBlocks(string $content): string
    {
        $patterns = [
            // @php ... @endphp blocks
            '/@php\b.*?@endphp/si',
            // @inject directive
            '/@inject\s*\([^)]*\)/i',
            // Dangerous includes that load arbitrary files
            '/@include[a-zA-Z]*\s*\([^)]*\)/i',
            '/@require[a-zA-Z]*\s*\([^)]*\)/i',
            // Layout directives that could load arbitrary files
            '/@extends\s*\([^)]*\)/i',
            // Component loading
            '/@component[a-zA-Z]*\s*\([^)]*\)/i',
            '/@livewire[a-zA-Z]*\s*\([^)]*\)/i',
        ];

        foreach ($patterns as $pattern) {
            $content = preg_replace($pattern, '', $content) ?? $content;
        }

        return $content;
    }

    protected function removeUnsafeBladeDirectives(string $content): string
    {
        $dangerous = [
            // Dangerous PHP functions that could appear in Blade expressions
            '/\beval\s*\([^)]*\)/i',
            '/\bsystem\s*\([^)]*\)/i',
            '/\bexec\s*\([^)]*\)/i',
            '/\bshell_exec\s*\([^)]*\)/i',
            '/\bpassthru\s*\([^)]*\)/i',
            '/\bpopen\s*\([^)]*\)/i',
            '/\bproc_open\s*\([^)]*\)/i',
            '/\bfile_put_contents\s*\([^)]*\)/i',
            '/\bfile_get_contents\s*\([^)]*\)/i',
            '/\breadfile\s*\([^)]*\)/i',
            '/\bfile\s*\([^)]*\)/i',
            '/\bscandir\s*\([^)]*\)/i',
            '/\bglob\s*\([^)]*\)/i',
            '/\bbase64_decode\s*\([^)]*\)/i',
            '/\bstr_rot13\s*\([^)]*\)/i',
            '/\bgzinflate\s*\([^)]*\)/i',
            '/\bgzuncompress\s*\([^)]*\)/i',
            '/\bgzdecode\s*\([^)]*\)/i',
            '/\bpreg_replace\s*\(\s*[\'"].*?e[\'"]/i',
            '/\bcreate_function\s*\([^)]*\)/i',
            '/\bassert\s*\([^)]*\)/i',
            '/\bphpinfo\s*\([^)]*\)/i',
            '/\bgetallheaders\s*\([^)]*\)/i',
            '/\bheader\s*\([^)]*\)/i',
            '/\bsetcookie\s*\([^)]*\)/i',
            '/\bmove_uploaded_file\s*\([^)]*\)/i',
            '/\bunlink\s*\([^)]*\)/i',
            '/\brmdir\s*\([^)]*\)/i',
            '/\bmkdir\s*\([^)]*\)/i',
            '/\bchmod\s*\([^)]*\)/i',
            '/\bchown\s*\([^)]*\)/i',
        ];

        foreach ($dangerous as $pattern) {
            $content = preg_replace($pattern, '', $content) ?? $content;
        }

        return $content;
    }

    protected function removeUnescapedOutput(string $content): string
    {
        // {!! unescaped !!} - force all output through Blade's escaping
        return preg_replace('/\{!!\s*.+?\s*!!\}/s', '', $content) ?? $content;
    }

    protected function removeObfuscation(string $content): string
    {
        $patterns = [
            '/\\\\x[0-9a-fA-F]{2}/i',  // \x47 hex encoding
            '/\\\\u[0-9a-fA-F]{4}/i',  // \u0047 unicode
            '/chr\s*\(\s*\d+\s*\)/i',  // chr(72) char concatenation
            '/GLOBALS\s*\[[^\]]*\]/i',  // $GLOBALS['var']
        ];

        foreach ($patterns as $pattern) {
            $content = preg_replace($pattern, '', $content) ?? $content;
        }

        return $content;
    }

    protected function removeVariableVariables(string $content): string
    {
        // $$var and ${...} variable variables
        $content = preg_replace('/\$\$[a-zA-Z_\x7f-\xff]/i', '', $content) ?? $content;

        return preg_replace('/\$\{[^}]*\}/i', '', $content) ?? $content;
    }

    protected function removePathTraversal(string $content): string
    {
        $content = preg_replace('/\.\.\//i', '', $content) ?? $content;

        return preg_replace('/\.\.\\\\/i', '', $content) ?? $content;
    }
}
