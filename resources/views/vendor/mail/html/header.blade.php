@props(['url'])
<tr>
  <td style="padding: 28px 32px; text-align: center;">
    <a href="{{ $url }}" style="text-decoration: none; display: inline-block;">
      <img
        src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo-evalium.png'))) }}"
        width="210"
        height="48"
        alt="Evalium"
        style="display: block; border: 0; max-width: 210px;">
    </a>
  </td>
</tr>