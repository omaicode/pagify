<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex,nofollow">
	<title>Pagify Page Builder Editor Host</title>
</head>
<body>
	<div id="pbx-editor-host-root" data-page-builder-editor-host="true"></div>

	<script>
		window.PagifyPageBuilderEditorBoot = @json($bootPayload);
	</script>

	@if ($entryScriptUrl !== '')
		<script type="module" src="{{ $entryScriptUrl }}"></script>
	@else
		<pre style="padding:16px;border:1px solid #f1d5d5;background:#fff5f5;color:#b91c1c;white-space:pre-wrap;">Editor host bundle missing. Run: cd modules/page-builder && npm run build</pre>
	@endif
</body>
</html>
