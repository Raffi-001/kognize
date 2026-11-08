@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; color: #0a74da; font-family: 'Bricolage Grotesque', 'Noto Sans Armenian', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 32px; font-weight: bold; letter-spacing: -0.5px; line-height: 1; text-decoration: none;">
{!! $slot !!}
</a>
</td>
</tr>
