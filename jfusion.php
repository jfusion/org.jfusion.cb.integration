<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

//require JFusion's framework
require_once JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_jfusion' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'model.factory.php';

/**
 * @ignore
 * @var $mainframe JApplicationCms
 */
global $mainframe;
$UElanguagePath = (str_replace('/administrator', '', JPATH_SITE)) . '/components/com_comprofiler/plugin/user/plug_jfusionintegration/language';
if ( file_exists($UElanguagePath . '/' . $mainframe->get('lang') . '.php')) {
    include_once($UElanguagePath . '/' . $mainframe->get('lang') . '.php');
} else {
    include_once($UElanguagePath . '/default_language.php');
}

global $_PLUGINS;
$_PLUGINS->registerFunction('onUserActive', 'userActivated', 'getjfusionTab');

/**
 * Class getjfusionTab
 */
class getjfusionTab extends cbTabHandler {
	/**
	 * @var JRegistry
	 */
	var $params;

	function getjfusionTab() {
        $this->cbTabHandler();
    }

    /**
    * Generates the HTML to display the user profile tab
    * @param object tab reflecting the tab database entry
    * @param object mosUser reflecting the user being displayed
    * @param int 1 for front-end, 2 for back-end
    * @returns mixed : either string HTML for tab content, or false if ErrorMSG generated
    */
    function getDisplayTab($tab,$user,$ui) {
        global $my;
        $itemid = $this->params->get('itemid');
        if (is_numeric($itemid)) {
	        $app = JFactory::getApplication();
	        $menu = $app->getMenu('site');
            $menu_param = $menu->getParams($itemid);
            $plugin_param = unserialize(base64_decode($menu_param->get('JFusionPluginParam')));
            $jname = $plugin_param['jfusionplugin'];
        } else {
            $jname = $itemid;
        }
        $show_avatar = $this->params->get('show_avatar', 1);
        $show_pm_count = $this->params->get('show_pm_count', 1);
        $show_profilelink = $this->params->get('show_profilelink', 1);
        $show_activity = $this->params->get('show_activity', 1);
        $show_new_forummessages = $this->params->get('show_new_forummessages', 1);
        //make sure a jfusion plugin has bene selected
        if (empty($jname)) {
            return 'No plugin selected!';
        }

        //make sure we have a matched user
        $userlookup = JFusionFunction::lookupUser($jname, $user->id, $user->username);

        if (empty($userlookup)) {
            return 'No user found!';
        }

        $document = JFactory::getDocument();
        $document->addStylesheet(JURI::root() . 'components/com_comprofiler/plugin/user/plug_jfusionintegration/jfusion.css');

	    $public = JFusionFactory::getPublic($jname);
	    $forum = JFusionFactory::getForum($jname);

        $return = "<div class = 'jfusion_cb_container'>\n";
        if ($show_avatar) {
            $avatar = $forum->getAvatar($userlookup->userid);
            if (!empty($avatar)) {
                $return .= "<div class='jfusion_cb_avatar'>\n";
                $return .= "<img src='$avatar' />";
                $return .= '</div>';
            }
        }

        if (!$my->guest && $my->id == $user->id) {
            $lang = JFactory::getLanguage();
            $lang->load('mod_jfusion_user_activity');

            if ($show_pm_count) {
                $return .= "<div class='jfusion_cb_pm' >\n";
                $url_pm = JFusionFunction::routeURL($forum->getPrivateMessageURL(), $itemid);
                $pmcount = $forum->getPrivateMessageCounts($userlookup->userid);
                $pm = _UE_PM_START;
                $pm.= ' <a href="' . $url_pm . '">' . sprintf(_UE_PM_LINK, $pmcount["total"]) . '</a>';
                $pm.= sprintf(_UE_PM_END, $pmcount["unread"]);
                $return .= $pm . "</div>\n";
            }

            if($show_new_forummessages) {
                $return.= "<div class='jfusion_cb_newmessages' >";
                $url_viewnewmessages = JFusionFunction::routeURL($forum->getViewNewMessagesURL(), $itemid);
                $return.= "<a href='$url_viewnewmessages'>" . _UE_VIEW_NEW_TOPICS . "</a></div>\n";
            }
        }

        if ($show_profilelink) {
            $profilelink = $forum->getProfileURL($userlookup->userid);
            if (!empty($profilelink)) {
                $link = JFusionFunction::routeURL($profilelink, $itemid);
                $return .= "<div class='jfusion_cb_profilelink' >\n";
                $profiletext = ($my->guest || $my->id != $user->id) ? _UE_VIEW_USERS_PROFILE : _UE_VIEW_YOUR_PROFILE;
                $return .= "<a href='$link'>$profiletext</a><br />";
                $return .= "</div>\n";
            }
        }
        $return .= "<div style='clear:both;'></div>\n";

        if ($show_activity && !empty($userlookup)) {
            $return .= "<div class='jfusion_cb_useractivity' >\n";
            defined('_DATE_FORMAT_LC2') or define('_DATE_FORMAT_LC2', '%A, %d %B %Y %H:%M');
            defined('LAT') or define('LAT', 0);
            defined('LCT') or define('LCT', 1);
            defined('LCP') or define('LCP', 2);
            defined('LINKTHREAD') or define('LINKTHREAD', 0);
            defined('LINKPOST') or define('LINKPOST', 1);
            // configuration
            $config['linktype'] = $this->params->get('linktype', 0);
            $config['display_body'] = $this->params->get('display_body', 0);
            $config['replace_subject'] = $this->params->get('replace_subject', 0);
            $config['new_window'] = $this->params->get('new_window', 0);
            $config['forum_mode'] = $this->params->get('forum_mode', 0);
            $config['character_limit'] = $this->params->get('character_limit');
            $config['character_limit_subject'] = $this->params->get('character_limit_subject');
            $config['result_limit'] = $this->params->get('result_limit');
            $config['date_format'] = $this->params->get('custom_date', _DATE_FORMAT_LC2);
            if (empty($config['date_format'])) {
                $config['date_format'] = _DATE_FORMAT_LC2;
            }
            $config['tz_offset'] = $this->params->get('tz_offset', 0);
            $config['result_order'] = ($this->params->get('result_order', 0)) ? 'DESC' : 'ASC';
            $config['showdate'] = $this->params->get('showdate', 1);
            $config['showuser'] = $this->params->get('showuser', 1);
            $config['display_name'] = $this->params->get('display_name', 0);
            $config['shownew'] = $this->params->get('shownew', 0);
            $config['userlink'] = $this->params->get('userlink', 0);
            $config['userlink_software'] = $this->params->get('userlink_software', false);
            $config['userlink_custom'] = $this->params->get('userlink_custom', false);
            $config['avatar'] = $this->params->get('show_activity_avatar', 0);
            $config['avatar_software'] = $this->params->get('avatar_software', 'jfusion');
            $config['avatar_height'] = $this->params->get('avatar_height', 53);
            $config['avatar_width'] = $this->params->get('avatar_width', 40);
            $config['itemid'] = $this->params->get('itemid');
            //can be used in plugins filterActivityResults
            defined('ACTIVITY_MODE') or define('ACTIVITY_MODE', $config['forum_mode']);
            if ($this->params->get('new_window')) {
                $config['new_window'] = '_blank';
            } else {
                $config['new_window'] = '_self';
            }
            $config['selected_forums'] = $this->params->get('selected_forums_' . $jname);

	        $return .= '<h3>' . _UE_USER_FORUM_ACTIVITY . '</h3>';
	        try {
		        $db = JFusionFactory::getDatabase($jname);
		        if ($config['forum_mode'] == 0 || empty($config['selected_forums'])) {
			        $selectedforumssql = '';
		        } else if (is_array($config['selected_forums'])) {
			        $selectedforumssql = implode(',', $config['selected_forums']);
		        } else {
			        $selectedforumssql = $config['selected_forums'];
		        }
		        //define some other JFusion specific parameters
		        $query = $forum->getActivityQuery($selectedforumssql, $config['result_order'], $config['result_limit'], array('userid', $userlookup->userid));

		        // load
		        $db->setQuery($query[LCP]);
		        $result = $db->loadObjectList();

		        $forum->filterActivityResults($result, $config['result_limit']);
		        //reorder the keys for the for loop
		        if (is_array($result)) {
			        $result = array_values($result);
		        }

				if (!$result) {
			        $return .= _UE_NO_POSTS;
		        } else {
			        $return .= '<ul>';
			        // process result
			        $row = 0;
			        for ($i = 0;$i < count($result);$i++) {
				        $user_html = ' ';
				        //get the Joomla userid
				        $userlookup = JFusionFunction::lookupUser($jname, $result[$i]->userid, false, $result[$i]->username);
				        if ($config['avatar']) {
					        // retrieve avatar
					        $avatarSrc = $config['avatar_software'];
					        if ($jname != 'joomla_int' && $jname != 'joomla_ext' && ($avatarSrc == '' || $avatarSrc == 'jfusion')) {
						        $avatarImg = $forum->getAvatar($result[$i]->userid);
					        } elseif (!empty($avatarSrc) && $avatarSrc != 'jfusion' && !empty($userlookup)) {
						        $avatarImg = JFusionFunction::getAltAvatar($avatarSrc, $userlookup->id);
					        }
					        if (empty($avatarImg)) {
						        $avatarImg = JFusionFunction::getJoomlaURL() . 'components/com_jfusion/images/noavatar.png';
					        }

					        $maxheight = $config['avatar_height'];
					        $maxwidth = $config['avatar_width'];
					        $avatar = "<img class='activity_avatar' style='";
					        $avatar.= (!empty($maxheight)) ? " max-height: {$maxheight}px;" : '';
					        $avatar.= (!empty($maxwidth)) ? " max-width: {$maxheight}px;" : '';
					        $avatar.= "' src='$avatarImg' alt='avatar' />";
				        } else {
					        $avatar = '';
				        }

				        //process user info
				        if ($config['showuser']) {
					        $displayname = ($config['display_name'] && !empty($result[$i]->name)) ? $result[$i]->name : $result[$i]->username;
					        if ($config['userlink'] && empty($result[$i]->guest)) {
						        if ($config['userlink_software'] != '' && $config['userlink_software'] != 'jfusion' && $config["userlink_software"] != 'custom' && !empty($userlookup)) {
							        $joomla_int = JFusionFactory::getForum('joomla_int');
							        $user_url = $joomla_int->getProfileURL($userlookup->id);
						        } elseif ($config['userlink_software'] == 'custom' && !empty($config['userlink_custom']) && !empty($userlookup)) {
							        $user_url = $config['userlink_custom'] . $userlookup->id;
						        } else {
							        $user_url = false;
						        }
						        if ($user_url === false) {
							        $user_url = JFusionFunction::routeURL($forum->getProfileURL($result[$i]->userid, $result[$i]->username), $config['itemid']);
						        }
						        $user_html = '<a href="' . $user_url . '" target="' . $config['new_window'] . '">' . $displayname . '</a>';
					        } else {
						        $user_html = $displayname;
					        }
					        $user_html = " - <span class='activity_user'>$user_html</span>";
				        }
				        //process date info
				        if ($config['showdate']) {
					        jimport('joomla.utilities.date');
					        $JDate = new JDate($result[$i]->dateline);
					        $JDate->setOffset($config['tz_offset']);
					        $date = $JDate->toFormat($config['date_format']);
				        } else {
					        $date = ' ';
				        }

				        //process subject or body info
				        $subject = (($config['replace_subject'] == 0 && empty($result[$i]->subject)) || $config['replace_subject'] == 1) ? $result[$i]->body : $result[$i]->subject;
				        //make sure that a message is always shown
				        if (empty($subject)) {
					        $subject = _UE_NO_SUBJECT;
				        } elseif (!empty($config['character_limit_subject']) && JString::strlen($subject) > $config['character_limit_subject']) {
					        //we need to shorten the subject
					        $subject = JString::substr($subject, 0, $config['character_limit_subject']) . '...';
				        }

				        //combine all info into an urlstring
				        $urlstring = '<span class="activity_title">';
				        if ($config['linktype'] == LINKPOST) {
					        $urlstring_pre = JFusionFunction::routeURL($forum->getPostURL($result[$i]->threadid, $result[$i]->postid), $config['itemid']);
					        $urlstring.= '<a href="' . $urlstring_pre . '" target="' . $config['new_window'] . '">' . $subject . '</a>';
				        } else {
					        $urlstring_pre = JFusionFunction::routeURL($forum->getThreadURL($result[$i]->threadid), $config['itemid']);
					        $urlstring.= '<a href="' . $urlstring_pre . '" target="' . $config['new_window'] . '">' . $subject . '</a>';
				        }
				        $urlstring.= '</span>';

				        //gotta make it presentable
				        if ($config['display_body'] == 1) {
					        $body = $result[$i]->body;
					        $status = $public->prepareText($body,'activity', $this->params, $result[$i]);
					        if (!empty($config['character_limit']) && empty($status['limit_applied']) && JString::strlen($body) > $config['character_limit']) {
						        $body = JString::substr($body, 0, $config['character_limit']) . '...';
					        }
					        $body = "<br />" . $body;
				        } else {
					        $body = '';
				        }

				        //put it all together for output
				        //prevents the images from cascading
				        $liStyle = (!empty($avatar)) ? ' style="clear:left;"' : '';
				        if ($config['shownew'] && method_exists($forum, 'checkReadStatus') && $forum->checkReadStatus($result[$i])) {
					        $newicon = '<img src="' . JFusionFunction::getJoomlaURL() . 'components/com_jfusion/images/new.png" style="margin-left:2px; margin-right:2px;"/>';
				        } else {
					        $newicon = ' ';
				        }
				        $return.= '<li' . $liStyle . ' class="activity_row' . $row . '">' . $avatar . $urlstring;
				        $row = ($row) ? 0 : 1;

				        if ($newicon) {
					        $return .= " $newicon";
				        }
				        if ($user) {
					        $return .= $user_html;
				        }
				        if ($date) {
					        $return .= " $date";
				        }
				        if ($body) {
					        $return .= "$body";
				        }
				        $return .= '<div class="activity_clearfix"></div></li>';
			        }
			        $return.= '</ul>';
		        }
		        $return .= "</div>\n";
	        } catch (Exception $e) {
		        $return .= $e->getMessage();
	        }
        }
        $return .= '</div>';

        return $return;
    }

	/**
	 * @param $name
	 * @param $value
	 * @param $control_name
	 *
	 * @return mixed
	 */
	function getJFusionPlugins($name,$value,$control_name) {
        $db = JFactory::getDBO();
        $query = "SELECT * FROM #__jfusion WHERE status = 1 AND name NOT LIKE 'joomla_int'";
        $db->setQuery($query);
        $plugins = $db->loadObjectList();
        $list = array();
        if ($plugins !== false) {
            foreach ($plugins as $plugin) {
                $list[] = moscomprofilerHTML::makeOption( $plugin->name, $plugin->name );
            }
        }
        $valAsObj = (isset($value)) ?
        array_map(create_function('$v', '$o=new stdClass(); $o->value=$v; return $o;'), explode("|*|", $value ))
        : null;

        return moscomprofilerHTML::selectList( $list, $control_name .'['. $name .'][]', 'class="inputbox"', 'value', 'text', $valAsObj, true );
    }

	/**
	 * @param $name
	 * @param $value
	 * @param $control_name
	 *
	 * @return string
	 */
	function getJFusionItemids($name, $value, $control_name) {
        global $mainframe;
        static $elId;
        if (!is_int($elId)) {
            $elId = 0;
        } else {
            $elId++;
        }
        $db = JFactory::getDBO();
        $doc = JFactory::getDocument();
        $template = $mainframe->getTemplate();
        $fieldName = $control_name . '[' . $name . ']';
        $js = "
        function jSelectItemid(name,id,num) {
            document.getElementById(name+'_id'+num).value = id;
            document.getElementById(name+'_name'+num).value = id;
            document.getElementById('sbox-window').close();
        }";
        $doc->addScriptDeclaration($js);
        $link = 'index.php?option=com_jfusion&amp;task=itemidselect&amp;tmpl=component&amp;ename=' . $name . '&amp;elId=' . $elId;
        JHTML::_('behavior.modal', 'a.modal');
        $html = "\n" . '<div style="float: left;"><input style="background: #ffffff;" type="text" id="' . $name . '_name' . $elId . '" value="' . $value . '" disabled="disabled" /></div>';
        $html.= '<div class="button2-left"><div class="blank"><a class="modal" title="' . _UE_SELECT_PLUGIN . '"  href="' . $link . '" rel="{handler: \'iframe\', size: {x: 650, y: 375}}">' . _UE_SELECT . '</a></div></div>' . "\n";
        $html.= "\n" . '<input type="hidden" id="' . $name . '_id' . $elId . '" name="' . $fieldName . '" value="' . $value . '" />';
        return $html;
    }

	/**
	 * @param $name
	 * @param $value
	 * @param $control_name
	 *
	 * @return mixed|string
	 */
	function getJFusionForumlist($name, $value, $control_name) {
		$cids = JFactory::getApplication()->input->get('cid', array(), 'array');
        $tabid = $cids[0];
        $db = JFactory::getDBO();
        $query = "SELECT params FROM #__comprofiler_tabs WHERE tabid = $tabid";
        $db->setQuery($query);
        $rawParams = $db->loadResult();

        $params = new cbParamsBase($rawParams);

        $itemid = $params->get('itemid');

        if (is_numeric($itemid)) {
            $app = JFactory::getApplication('site');
            $menu = $app->getMenu();
            $menu_param = $menu->getParams($itemid);
            $plugin_param = unserialize(base64_decode($menu_param->get('JFusionPluginParam')));
            $jname = $plugin_param['jfusionplugin'];
        } else {
            $jname = $itemid;
        }

        if ($jname) {
            $forum = JFusionFactory::getForum($jname);
            $forumlist = $forum->getForumList();
            $return = JHTML::_('select.genericlist', $forumlist, $control_name . '[' . $name . '][]', 'multiple size="6" class="inputbox"', 'id', 'name', $value);
        } else {
            $return = NO_PLUGIN_SELECT;
        }
        return $return;
    }

    /**
     * Updates the user in other softwares after CB activates a user
     */
    function userActivated($user, $success)
    {
        if ($success) {
            //update JFusion's plugins activation status
	        $plugins = JFusionFactory::getPlugins();

            //add a couple items in the way JFusion uses it
            $user->group_id = $user->gid;
	        $user->groups = $user->gids;

            $user->userid = $user->id;
            if (strpos($user->password, ':') !== false) {
                $saltStart = strpos($user->password, ':');
                $user->password_salt = substr($user->password, $saltStart + 1);
                $user->password = substr($user->password, 0, $saltStart);
            }

            foreach ($plugins as $plugin) {
                if ($plugin->name != 'joomla_int') {
                    $JFusionUser = JFusionFactory::getUser($plugin->name);
                    $status = $JFusionUser->updateUser($user, 0);
                }
            }
        }
        return true;
    }
}

/**
 * @param $itemid
 * @param $user
 *
 * @return array
 */
function getJFusionVars($itemid, &$user)
{
    static $jfusion_vars;

    if (!is_array($jfusion_vars)) {
        $jfusion_vars = array();
    }

    if (empty($jfusion_vars[$itemid])) {
        if (is_numeric($itemid)) {
	        $app = JFactory::getApplication();
	        $menu = $app->getMenu('site');
            $menu_param = $menu->getParams($itemid);
            $plugin_param = unserialize(base64_decode($menu_param->get('JFusionPluginParam')));
            $jname = $plugin_param['jfusionplugin'];
        } else {
            $jname = $itemid;
        }

        if (empty($jname)) {
            $jfusion_vars[$itemid] = false;
        }

	    $plugin = JFusionFactory::getPublic($jname);

        $jfusion_vars[$itemid] = array('jname' => $jname, 'plugin' => $plugin);
    }

    //make sure we have a matched user
    $userlookup = JFusionFunction::lookupUser($jfusion_vars[$itemid]->jname, $user->id, $user->username);

    if (empty($userlookup)) {
        $jfusion_vars[$itemid] = false;
    }

    return $jfusion_vars;
}