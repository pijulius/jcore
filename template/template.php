<!DOCTYPE html>
<html>
<head>
<title>
<?php
	url::displayPageTitle();
?>
</title>
<meta charset="<?php echo PAGE_CHARSET; ?>" />
<meta name="title" content="<?php url::displayPageTitle(); ?>" />
<meta name="keywords" content="<?php url::displayPageKeywords(); ?>" />
<meta name="description" content="<?php url::displayPageDescription(); ?>" />
<link rel="icon" type="image/png" href="<?php url::displayRootPath(); ?>template/images/favicon.png" />
<?php jQuery::display(); ?>
<?php css::display(); ?>
<?php rss::displayFeeds(); ?>
</head>
<body>
<?php
	include_once('lib/blocks.class.php');
	$blocks = new blocks();
	$blocks->display();
	unset($blocks);
?>
<?php jQuery::displayPlugins(); ?>
</body>
</html>