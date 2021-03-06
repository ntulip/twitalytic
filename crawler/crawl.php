<?php   
require_once('config.crawler.inc.php');
ini_set("include_path", ini_get("include_path").":".$INCLUDE_PATH);
require_once("init.php");

// Instantiate and initialize needed objects
$db = new Database();
$conn = $db->getConnection();
$id = new InstanceDAO();
$oid = new OwnerInstanceDAO();

$instances = $id->getAllInstancesStalestFirst();
foreach ($instances as $i) {
	$crawler = new Crawler($i);
	$cfg = new Config($i->twitter_username, $i->twitter_user_id);
	$logger = new Logger($i->twitter_username);
	$tokens = $oid->getOAuthTokens($i->id);
	$api = new CrawlerTwitterAPIAccessorOAuth($tokens['oauth_access_token'], $tokens['oauth_access_token_secret'], $cfg, $i);
	$api -> init($logger);

	if ( $api->available_api_calls_for_crawler > 0 ) {

		$id->updateLastRun($i->id);
		
		$crawler->fetchInstanceUserInfo($cfg, $api, $logger);

		$crawler->fetchInstanceUserTweets($cfg, $api, $logger);

		$crawler->fetchInstanceUserReplies($cfg, $api, $logger);

		$crawler->fetchInstanceUserFriends($cfg, $api, $logger);

		$crawler->fetchInstanceUserFollowers($cfg, $api, $logger);

		$crawler->fetchStrayRepliedToTweets($cfg, $api, $logger);

		$crawler->fetchUnloadedFollowerDetails($cfg, $api, $logger);

		$crawler->fetchFriendTweetsAndFriends($cfg, $api, $logger);

		// TODO: Get direct messages
		// TODO: Gather favorites data

		$crawler->cleanUpFollows($cfg, $api, $logger);
	
		// Save instance
		$id->save($crawler->instance,  $crawler->owner_object->tweet_count, $logger, $api);
	} 
	$logger->close();			# Close logging
}

if ( isset($conn) ) $db->closeConnection($conn); // Clean up
?>