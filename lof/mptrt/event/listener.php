<?php
/**
*
* Minimum posts to read topic
*
* @copyright (c) 2015 LordOfFlies - Special thanks to BruninoIt
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/
namespace lof\mptrt\event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
class listener implements EventSubscriberInterface
{
	/** @var \phpbb\config\config */	
	protected $config;
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;
	/** @var \phpbb\template\template */
	protected $template;
	/** @var \phpbb\auth\auth */
	protected $auth;
	/** @var \phpbb\user */
	protected $user;
	protected $root_path;
	
	protected $phpEx;
/** 
 	* Constructor 
 	* 
 	* @param \phpbb\config\config   		$config             	 Config object 
 	* @param \phpbb\db\driver\driver_interface      $db        	 	 DB object 
 	* @param \phpbb\template\template    		$template  	 	 Template object 
 	* @param \phpbb\auth\auth      			$auth           	 Auth object 
 	* @param \phpbb\use		     		$user           	 User object 
 	* @param	                		$root_path          	 Root Path object 
 	* @param                  	     		$phpEx          	 phpEx object 
 	* @return \staffit\toptentopics\event\listener 
 	* @access public 
 	*/ 
public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\template\template $template, \phpbb\auth\auth $auth, \phpbb\user $user, $root_path, $phpEx) 
{
   $this->config = $config;
   $this->db = $db;
   $this->template = $template; 
   $this->auth = $auth;
   $this->user = $user;
   $this->root_path = $root_path;
   $this->phpEx   = $phpEx ;
}
/** 
 	* Assign functions defined in this class to event listeners in the core 
 	* 
 	* @return array 
 	* @static 
 	* @access public 
 	*/ 
static public function getSubscribedEvents()	
{
return array(			
'core.user_setup'						=> 'setup',
'core.viewtopic_modify_post_row' => 'viewtopic_add',
'core.modify_posting_auth'=> 'pauth',
'core.posting_modify_template_vars' => 'template'
);	
}
public function setup($event)	{	
//language start
$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'lof/mptrt',
			'lang_set' => 'common',
		);
		$event['lang_set_ext'] = $lang_set_ext;
}
public function viewtopic_add($event)	
{
$rowmessage=$event['post_row'];
$message=$rowmessage['MESSAGE'];
if(strpos($message,"minPosts]"))
{

$userPosts = $this->user->data['user_posts'];

preg_match_all("#\[minPosts=(.*?)\]#", $message, $aposts);
if($aposts[1][0])
{
foreach($aposts[1] as $posts)
{
$lang=sprintf($this->user->lang['NEED_MORE_POSTS'], $posts, ($posts - $userPosts));
preg_match_all("#\[minPosts=$posts\](.*?)\[/minPosts\]#", $message, $amessage);

if($amessage[1][0])
{
foreach($amessage[1] as $msg)
{
if($userPosts < $posts)
{
$message = str_replace("[minPosts=$posts]$msg", $lang, $message); // Replace content with "You need more posts to view this... "
}
$message = str_replace("[minPosts=$posts]", "", $message);  // Blank first part of BBCode 
}
}
}
}
}
$message = str_replace("[/minPosts]","", $message); // Blank rest of BBCode

$rowmessage['MESSAGE'] = $message;
$event['post_row'] = $rowmessage;
}

public function pauth($event)
{
/*
draft_id, error, forum_id, is_authed, lastclick, load, mode, post_id, preview, refresh, save, submit, topic_id
*/
$post_id = $event['post_id'];
$mode = $event['mode'];

if($mode=="quote" and $post_id)
{
$error = 0;
$ut = $this->user->data['user_posts'];


$qa = $this->db->sql_query("SELECT post_text FROM ".POSTS_TABLE." WHERE post_id=$post_id");
$ra = $this->db->sql_fetchrow($qa);
$message = $ra['post_text'];

if(strpos($message,"minPosts]"))
{
preg_match_all("#\[minPosts=(.*?)\]#", $message, $atopics);
if($atopics[1][0])
{
foreach($atopics[1] as $post)
{
if($ut < $post)
{
$error = 1;
}
}
}
}
}
if($error)
{
trigger_error('CANT_QUOTE');
}
}

public function template($event)
{
/*
cancel, draft_id, error, form_enctype, forum_id, load, message_parser, mode, moderators, page_data, page_title, post_data, post_id, preview, refresh, s_action, s_hidden_fields, s_topic_icons, save, submit, topic_id
*/
$ut = $this->user->data['user_posts'];

$topic_id = $event['topic_id'];
$qa = $this->db->sql_query("SELECT post_text FROM ".POSTS_TABLE." WHERE topic_id=$topic_id");

while($r = $this->db->sql_fetchrow($qa))
{
$message = $r['post_text'];
if(strpos($message,"minPosts]"))
{
preg_match_all("#\[minPosts=(.*?)\]#", $message, $atopics);
if($atopics[1][0])
{
foreach($atopics[1] as $topics)
{
if($ut < $topics)
{
$error = 1;
}
}
}
}
}
if($error)
{
$event['mode'] = "ahahahahaha";
}
}

}
