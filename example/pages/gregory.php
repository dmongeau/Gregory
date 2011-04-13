<?php

$gregory = file_get_contents('https://github.com/dmongeau/Gregory/raw/master/Gregory.php');

$this->addScript('/statics/js/syntaxHighlighter/shCore.js');
$this->addScript('/statics/js/syntaxHighlighter/shBrushPhp.js');
$this->addStylesheet('/statics/css/syntaxHighlighter/shCore.css');
$this->addStylesheet('/statics/css/syntaxHighlighter/shThemeDefault.css');

?>

<div style="font-size:12px;">
	<pre class="brush: php"><?=htmlentities($gregory)?></pre>
</div>
<script type="text/javascript">
     SyntaxHighlighter.all()
</script>