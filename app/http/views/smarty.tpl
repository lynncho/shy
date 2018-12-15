<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{$title}</title>
    <link type="text/css" rel="stylesheet" href="{BASE_URL}css/app.css">
</head>
<body>
<div id="hello-world">
    {$info}
</div>
<div id="system">
    <p>Memory Peak:{memory_get_peak_usage()/1024} kb</p>
    {if IS_CLI}
        <p>Used Time: {microtime(true) - $GLOBALS['_SHY_START']} second</p>
    {else}
        <p>Used Time: {microtime(true) - SHY_START} second</p>
    {/if}
    <br>
    <p>Loaded instance's abstract: </p>
    <ol>
        {foreach shy_list_memory_used() as $abstract => $memoryUsed}
            <li>{$abstract}  {$memoryUsed} kb</li>
        {/foreach}
    </ol>
</div>
{include file='component/footer.php'}
</body>
</html>
