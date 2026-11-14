name = "Button"
==
{{ $slot }} <{{ $url }}>
==
<table class="action" align="center" width="100%" cellpadding="0" cellspacing="0" style="text-align: {{ $align ?? 'center' }};">
    <tr>
        <td align="{{ $align ?? 'center' }}">
            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="{{ $align ?? 'center' }}">
                        <table border="0" cellpadding="0" cellspacing="0" align="{{ $align ?? 'center' }}">
                            <tr>
                                <td>
                                    <a href="{{ $url }}" class="button button-{{ $type ?? 'primary' }}" target="_blank">{{ $slot }}</a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
