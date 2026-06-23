<?php

namespace App\Support;

/**
 * Table-based HTML building blocks for transactional emails.
 * All styles are inline for email client compatibility.
 */
class EmailLayout
{
    public const ACCENT = '#f05a28';

    public const INK = '#111318';

    public const INK_SECONDARY = '#5a6072';

    public const INK_MUTED = '#9ba3b4';

    public const LINE = '#e4e7ee';

    public const SURFACE = '#f7f8fa';

    public const CANVAS = '#f0f2f5';

    public const SUCCESS = '#16b364';

    public const DANGER = '#e53e3e';

    public const WARNING = '#e89a0d';

    /** Email-safe tints (no color-mix — poor client support). */
    public const ACCENT_TINT_BG = '#fef2ed';

    public const ACCENT_TINT_BORDER = '#fad4c4';

    public const ACCENT_BOX_BG = '#fff9f7';

    public const ACCENT_LABEL = '#c47852';

    public const SURFACE_MIX = '#fafbfc';

    public const WARNING_BG = '#fffbeb';

    public const WARNING_BORDER = '#fde68a';

    public const WARNING_TEXT = '#92400e';

    /**
     * @param  array{preheader?: string, title?: string, badge?: string|null}  $options
     */
    public static function wrap(string $content, array $options = []): string
    {
        $year = date('Y');
        $preheader = $options['preheader'] ?? '';
        $title = $options['title'] ?? 'Semrushtoolz';
        $badge = $options['badge'] ?? null;
        $header = self::brandHeader($badge);
        $preheaderBlock = $preheader !== ''
            ? '<div style="display:none;max-height:0;overflow:hidden;font-size:1px;line-height:1px;color:'.self::CANVAS.';">'.$preheader.'&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;</div>'
            : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>{$title}</title>
</head>
<body style="margin:0;padding:0;background-color:{self::CANVAS};font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;-webkit-font-smoothing:antialiased;">
{$preheaderBlock}
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:{self::CANVAS};">
<tr>
<td align="center" style="padding:48px 16px;">
<table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:20px;overflow:hidden;border:1px solid {self::LINE};box-shadow:0 4px 24px rgba(17,19,24,0.07),0 1px 4px rgba(17,19,24,0.04);">
<tr>
<td style="height:4px;background-color:{self::ACCENT};font-size:0;line-height:0;">&nbsp;</td>
</tr>
<tr>
<td style="padding:24px 40px;border-bottom:1px solid {self::LINE};">
{$header}
</td>
</tr>
<tr>
<td style="padding:40px 44px 32px 44px;color:{self::INK};">
{$content}
</td>
</tr>
<tr>
<td style="padding:24px 44px 32px 44px;background-color:{self::SURFACE};border-top:1px solid {self::LINE};">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
<tr>
<td align="center" style="padding-bottom:14px;">
<img src="{{favicon_url}}" alt="SemrushToolz" width="22" height="22" style="display:inline-block;opacity:0.35;border:0;border-radius:50%;">
</td>
</tr>
<tr>
<td align="center">
<p style="margin:0 0 8px 0;font-size:12px;line-height:1.5;color:{self::INK_MUTED};">
&copy; {$year} <strong style="color:{self::INK_MUTED};">Semrushtoolz</strong> &middot; Premium SEO tools at group-buy prices
</p>
</td>
</tr>
<tr>
<td align="center">
<p style="margin:0;font-size:12px;line-height:1.5;color:{self::INK_MUTED};">
<a href="{{site_url}}" style="color:{self::ACCENT};text-decoration:none;font-weight:600;">semrushtoolz.com</a>
&nbsp;&nbsp;&middot;&nbsp;&nbsp;
<a href="{{privacy_url}}" style="color:{self::INK_MUTED};text-decoration:none;">Privacy Policy</a>
&nbsp;&nbsp;&middot;&nbsp;&nbsp;
<a href="{{unsubscribe_url}}" style="color:{self::INK_MUTED};text-decoration:none;">Unsubscribe</a>
</p>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>
</body>
</html>
HTML;
    }

    public static function brandHeader(?string $badge = null): string
    {
        $badgeHtml = $badge !== null && $badge !== ''
            ? '<td align="right" style="vertical-align:middle;">
<span style="display:inline-block;background-color:'.self::ACCENT_TINT_BG.';color:'.self::ACCENT.';font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;padding:6px 14px;border-radius:100px;border:1px solid '.self::ACCENT_TINT_BORDER.';">
&#128274;&nbsp; '.htmlspecialchars($badge, ENT_QUOTES, 'UTF-8').'
</span>
</td>'
            : '<td></td>';

        return <<<HTML
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
<tr>
<td style="vertical-align:middle;">
<table role="presentation" cellspacing="0" cellpadding="0" border="0">
<tr>
<td style="vertical-align:middle;">
<img src="{{favicon_url}}" alt="SemrushToolz" width="34" height="34" style="display:block;border:0;border-radius:50%;object-fit:cover;">
</td>
<td style="padding-left:10px;vertical-align:middle;">
<span style="font-size:17px;font-weight:800;color:#111318;letter-spacing:-0.02em;">Semrushtoolz</span>
</td>
</tr>
</table>
</td>
{$badgeHtml}
</tr>
</table>
HTML;
    }

    public static function hero(string $eyebrow, string $title, ?string $subtitle = null): string
    {
        $subtitleHtml = $subtitle !== null && $subtitle !== ''
            ? '<p style="margin:0 0 28px 0;font-size:14px;color:#9ba3b4;font-weight:400;line-height:1.5;">'.$subtitle.'</p>'
            : '<div style="margin-bottom:28px;"></div>';

        return <<<HTML
<p style="margin:0 0 10px 0;font-size:11px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;color:{self::ACCENT};">{$eyebrow}</p>
<h1 style="margin:0 0 6px 0;font-size:26px;font-weight:800;line-height:1.2;color:#111318;letter-spacing:-0.03em;">{$title}</h1>
{$subtitleHtml}
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 28px 0;">
<tr><td style="height:1px;background-color:{self::LINE};font-size:0;line-height:0;">&nbsp;</td></tr>
</table>
HTML;
    }

    public static function greeting(string $nameVar = '{{name}}'): string
    {
        return '<p style="margin:0 0 4px 0;font-size:15px;line-height:1.65;color:'.self::INK_SECONDARY.';">Hello <strong style="color:'.self::INK.';font-weight:700;">'.$nameVar.'</strong>,</p>';
    }

    public static function paragraph(string $html): string
    {
        return '<p style="margin:0 0 28px 0;font-size:15px;line-height:1.65;color:'.self::INK_SECONDARY.';">'.$html.'</p>';
    }

    public static function paragraphTight(string $html): string
    {
        return '<p style="margin:0 0 16px 0;font-size:15px;line-height:1.65;color:'.self::INK_SECONDARY.';">'.$html.'</p>';
    }

    public static function otpBox(string $otpVar = '{{otp}}'): string
    {
        return <<<HTML
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 16px 0;">
<tr>
<td align="center" style="background-color:{self::ACCENT_BOX_BG};border:1.5px solid {self::ACCENT_TINT_BORDER};border-radius:16px;padding:28px 20px 24px 20px;">
<p style="margin:0 0 12px 0;font-size:10px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:{self::ACCENT_LABEL};">Verification Code</p>
<p style="margin:0;font-size:46px;font-weight:900;letter-spacing:0.48em;padding-left:0.48em;color:{self::ACCENT};font-family:'SF Mono',SFMono-Regular,Menlo,Monaco,Consolas,'Courier New',monospace;line-height:1;">{$otpVar}</p>
</td>
</tr>
</table>
HTML;
    }

    public static function expiryBadge(string $html): string
    {
        return <<<HTML
<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto 30px auto;">
<tr>
<td style="padding:9px 20px;background-color:{self::WARNING_BG};border:1px solid {self::WARNING_BORDER};border-radius:100px;font-size:12px;color:{self::WARNING_TEXT};font-weight:500;line-height:1.3;">
&#9200;&nbsp;&nbsp;{$html}
</td>
</tr>
</table>
HTML;
    }

    public static function metaLine(string $html): string
    {
        return self::expiryBadge($html);
    }

    public static function button(string $urlVar, string $label, string $color = self::ACCENT): string
    {
        return <<<HTML
<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 28px 0;">
<tr>
<td align="center" style="border-radius:12px;background-color:{$color};">
<a href="{$urlVar}" target="_blank" style="display:inline-block;padding:14px 36px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:12px;letter-spacing:-0.01em;">{$label}</a>
</td>
</tr>
</table>
HTML;
    }

    public static function buttonDanger(string $urlVar, string $label): string
    {
        return self::button($urlVar, $label, self::DANGER);
    }

    public static function detailsList(string $rowsHtml): string
    {
        return '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 28px 0;border:1px solid '.self::LINE.';border-radius:12px;overflow:hidden;">'.$rowsHtml.'</table>';
    }

    public static function detailRow(string $label, string $value): string
    {
        return '<tr><td style="padding:12px 16px;border-bottom:1px solid '.self::LINE.';font-size:13px;color:'.self::INK_MUTED.';width:40%;">'.$label.'</td><td style="padding:12px 16px;border-bottom:1px solid '.self::LINE.';font-size:14px;font-weight:600;color:'.self::INK.';">'.$value.'</td></tr>';
    }

    public static function highlightBox(string $html, string $borderColor = self::ACCENT): string
    {
        $bg = $borderColor === self::DANGER ? '#fef2f2' : ($borderColor === self::WARNING ? self::WARNING_BG : self::ACCENT_TINT_BG);
        $border = $borderColor === self::DANGER ? '#fecaca' : ($borderColor === self::WARNING ? self::WARNING_BORDER : self::ACCENT_TINT_BORDER);

        return <<<HTML
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 28px 0;">
<tr>
<td style="padding:16px 20px;background-color:{$bg};border:1px solid {$border};border-radius:12px;font-size:14px;line-height:1.65;color:{self::INK_SECONDARY};">
{$html}
</td>
</tr>
</table>
HTML;
    }

    public static function codeBlock(string $codeVar): string
    {
        return <<<HTML
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 28px 0;">
<tr>
<td style="padding:14px 16px;background-color:{self::SURFACE_MIX};border:1px solid {self::LINE};border-radius:12px;font-family:'SF Mono',SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:14px;color:{self::INK};word-break:break-all;line-height:1.5;">
{$codeVar}
</td>
</tr>
</table>
HTML;
    }

    public static function securityNotice(
        string $title = 'Didn\'t request this?',
        string $text = 'You can safely ignore this email. Your account remains secure and no changes have been made.',
    ): string {
        return <<<HTML
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0;">
<tr>
<td style="padding:16px 20px;background-color:{self::SURFACE_MIX};border:1px solid {self::LINE};border-radius:12px;">
<table role="presentation" cellspacing="0" cellpadding="0" border="0">
<tr>
<td style="vertical-align:top;padding-right:12px;font-size:16px;line-height:1.2;">&#128274;</td>
<td style="vertical-align:top;font-size:13px;line-height:1.65;color:#8b92a5;">
<strong style="color:#6b7280;font-weight:600;display:block;margin-bottom:3px;">{$title}</strong>
{$text}
</td>
</tr>
</table>
</td>
</tr>
</table>
HTML;
    }

    public static function divider(): string
    {
        return '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px 0;"><tr><td style="height:1px;background-color:'.self::LINE.';font-size:0;line-height:0;">&nbsp;</td></tr></table>';
    }

    public static function muted(string $html): string
    {
        return '<p style="margin:0 0 28px 0;font-size:12px;line-height:1.5;color:'.self::INK_MUTED.';">'.$html.'</p>';
    }

    public static function statBadge(string $value, string $label): string
    {
        return <<<HTML
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 28px 0;">
<tr>
<td align="center" style="background-color:{self::ACCENT_BOX_BG};border:1.5px solid {self::ACCENT_TINT_BORDER};border-radius:16px;padding:24px 20px;">
<p style="margin:0;font-size:32px;font-weight:900;color:{self::ACCENT};letter-spacing:-0.02em;">{$value}</p>
<p style="margin:8px 0 0 0;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.18em;color:{self::ACCENT_LABEL};">{$label}</p>
</td>
</tr>
</table>
HTML;
    }

    /** @deprecated Use footer links inside wrap() */
    public static function unsubscribe(string $urlVar = '{{unsubscribe_url}}'): string
    {
        return self::muted('<a href="'.$urlVar.'" style="color:'.self::INK_MUTED.';">Unsubscribe from promotional emails</a>');
    }
}
