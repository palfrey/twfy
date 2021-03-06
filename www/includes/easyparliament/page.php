<?php

if (defined('OPTION_TRACKING') && OPTION_TRACKING)
	require_once PHPLIBPATH . '/tracking.php';

include_once PHPLIBPATH . '/gaze.php';
include_once INCLUDESPATH . 'easyparliament/member.php';

class PAGE {

	// So we can tell from other places whether we need to output the page_start or not.
	// Use the page_started() function to do this.
	var $page_start_done = false;
	var $supress_heading = false;
	var $heading_displayed = false;
	
	// We want to know where we are with the stripes, the main structural elements
	// of most pages, so that if we output an error message we can wrap it in HTML
	// that won't break the rest of the page.
	// Changed in $this->stripe_start().
	var $within_stripe_main = false;
	var $within_stripe_sidebar = false;
	
	function page_start () {

	  ob_start();
	  set_time_limit(0);
		global $DATA, $this_page, $THEUSER;
		
		if (!$this->page_started()) {
			// Just in case something's already started this page...
			$parent = $DATA->page_metadata($this_page, "parent");
			if ($parent == 'admin' && (!$THEUSER->isloggedin() || !$THEUSER->is_able_to('viewadminsection'))) {
				// If the user tries to access the admin section when they're not
				// allowed, then show them nothing.
			
				if (!$THEUSER->isloggedin()) {
					$THISPAGE = new URL($this_page);
					
					$LOGINURL = new URL('userlogin');
					$LOGINURL->insert(array('ret' => $THISPAGE->generate('none') ));

					$text = "<a href=\"" . $LOGINURL->generate() . "\">You'd better sign in!</a>";
				} else {
					$text = "That's all folks!";
				}
				
				$this_page = 'home';
				
				$this->page_header();
				$this->page_body();
				$this->content_start();
				$this->stripe_start();
				
				print "<p>$text</p>\n";
				
				$this->stripe_end();
				$this->page_end();
				exit;
			}

			$this->page_header();
			$this->page_body();
			$this->content_start();
			
			$this->page_start_done = true;		
			
		}
	}
	
	
	function page_end ($extra = null) {
		$this->content_end();
		$this->page_footer($extra);
	}
	
	
	function page_started () {
		return $this->page_start_done == true ? true : false;
	}

	function heading_displayed () {
		return $this->heading_displayed == true ? true : false;
	}
	
	function within_stripe () {
		if ($this->within_stripe_main == true || $this->within_stripe_sidebar == true) {
			return true;
		} else {
			return false;
		}
	}

	function within_stripe_sidebar () {
		if ($this->within_stripe_sidebar == true) {
			return true;
		} else {
			return false;
		}
	}


	function page_header () {
		global $DATA, $this_page;
		
		$linkshtml = "";
	
		$title = '';
		$sitetitle = $DATA->page_metadata($this_page, "sitetitle");
		$keywords_title = '';
		
		if ($this_page == 'home') {
			$title = $sitetitle . ': ' . $DATA->page_metadata($this_page, "title");
		
		} else {

			if ($page_subtitle = $DATA->page_metadata($this_page, "subtitle")) {
				$title = $page_subtitle;
			} elseif ($page_title = $DATA->page_metadata($this_page, "title")) {
				$title = $page_title;
			}
			// We'll put this in the meta keywords tag.
			$keywords_title = $title;

			$parent_page = $DATA->page_metadata($this_page, 'parent');
			if ($parent_title = $DATA->page_metadata($parent_page, 'title')) {
				$title .= ": $parent_title";
			}

			if ($title == '') {
				$title = $sitetitle;
			} else {			
				$title .= ' (' . $sitetitle . ')';
			}
		}

		if (!$metakeywords = $DATA->page_metadata($this_page, "metakeywords")) {
			$metakeywords = "";
		}
		if (!$metadescription = $DATA->page_metadata($this_page, "metadescription")) {
			$metadescription = "";
		}

		if ($this_page != "home") {
			$URL = new URL('home');
			
			$linkshtml = "\t<link rel=\"start\" title=\"Home\" href=\"" . $URL->generate() . "\">\n";
		}

		// Create the next/prev/up links for navigation.
		// Their data is put in the metadata in hansardlist.php
		$nextprev = $DATA->page_metadata($this_page, "nextprev");

		if ($nextprev) {
			// Four different kinds of back/forth links we might build.
			$links = array ("first", "prev", "up", "next", "last");

			foreach ($links as $n => $type) {
				if (isset($nextprev[$type]) && isset($nextprev[$type]['listurl'])) {
				
					if (isset($nextprev[$type]['body'])) {
						$linktitle = htmlentities( trim_characters($nextprev[$type]['body'], 0, 40) );
						if (isset($nextprev[$type]['speaker']) &&
							count($nextprev[$type]['speaker']) > 0) {
							$linktitle = $nextprev[$type]['speaker']['first_name'] . ' ' . $nextprev[$type]['speaker']['last_name'] . ': ' . $linktitle;	
						}

					} elseif (isset($nextprev[$type]['hdate'])) {
						$linktitle = format_date($nextprev[$type]['hdate'], SHORTDATEFORMAT);
					}

					$linkshtml .= "\t<link rel=\"$type\" title=\"$linktitle\" href=\"" . $nextprev[$type]['listurl'] . "\">\n";
				}
			}
		}
		
		// Needs to come before any HTML is output, in case it needs to set a cookie.
		$SKIN = new SKIN();
			
		if (!$keywords = $DATA->page_metadata($this_page, "keywords")) {
			$keywords = "";	
		} else {
			$keywords = ",".$DATA->page_metadata($this_page, "keywords");
		}

		$robots = '';
		if ($robots = $DATA->page_metadata($this_page, 'robots')) {
			$robots = '<meta name="robots" content="' . $robots . '">';
		}

		header('Content-Type: text/html; charset=iso-8859-1');
        if ($this_page == 'home') {
            header('Vary: Cookie, X-GeoIP-Country');
            header('Cache-Control: max-age=600');
        }

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<title><?php echo $title; ?></title>
	<meta name="description" content="Making parliament easy.">
	<meta name="keywords" content="Parliament, government, house of commons, house of lords, MP, Peer, Member of Parliament, MPs, Peers, Lords, Commons, UK, Britain, British, Welsh, Scottish, Wales, Scotland, <?php echo htmlentities($keywords_title).htmlentities($keywords); ?>">
	<?=$robots ?>
	<link rel="author" title="Send feedback" href="mailto:<?php echo str_replace('@', '&#64;', CONTACTEMAIL); ?>">
	<link rel="home" title="Home" href="http://<?php echo DOMAIN; ?>/">
	<script type="text/javascript" src="/js/jquery.js"></script>
	<script type="text/javascript" src="/js/jquery.cookie.js"></script>
	<script type="text/javascript" src="/jslib/share/share.js"></script>
	<script type="text/javascript" src="/js/main.js"></script>	
	<script type="text/javascript" src="/js/bar.js"></script>	
<?php
		echo $linkshtml; 
		
		$SKIN->output_stylesheets();

		if ($rssurl = $DATA->page_metadata($this_page, 'rss')) {
			// If this page has an RSS feed set.
			echo '<link rel="alternate" type="application/rss+xml" title="TheyWorkForYou RSS" href="http://', DOMAIN, WEBPATH, $rssurl, '">';
		}

		if (!DEVSITE) {
?>

<script src="http://www.google-analytics.com/urchin.js"
type="text/javascript">
</script>
<script type="text/javascript">
_uacct = "UA-660910-1";
if (typeof urchinTracker == 'function') urchinTracker();
</script>

<?		}
		echo '</head>';
	}	

	function page_body () {
		global $this_page;
		
		// Start the body, put in the page headings.
		?>
<body>
<div id="container">
<?php
		twfy_debug ("PAGE", "This page: $this_page");
		
		print "\t<a name=\"top\"></a>\n\n";
		if (defined('OPTION_GAZE_URL') && OPTION_GAZE_URL) {
			$country = gaze_get_country_from_ip($_SERVER["REMOTE_ADDR"]);
			if ($country == 'NZ' || get_http_var('nz')) {
				print '<p id="video_already"><strong>New!</strong> You\'re in New Zealand, so check out <a href="http://www.theyworkforyou.co.nz">TheyWorkForYou.co.nz</a></p>';
			} elseif ($country == 'AU' || get_http_var('au')) {
				print '<p id="video_already"><strong>New!</strong> You\'re in Australia, so check out <a href="http://www.openaustralia.org">OpenAustralia</a>, a TheyWorkForYou for down under</p>';
			} elseif ($country == 'IE' || get_http_var('ie')) {
				print '<p id="video_already"><strong>New!</strong> Check out <a href="http://www.kildarestreet.com/">KildareStreet</a>, a TheyWorkForYou for the Houses of the Oireachtas</p>';
			} /* else {
				print '<p id="video_already"><strong>Mirror readers!</strong> <a href="http://www.writetothem.com/?a=westminstermp">Click here to write to your MP</a></p>';
            } */
		}

# # 2009-01 interstitial
# include INCLUDESPATH . '../docs/foiorder2009/fns.php';
# echo '<div id="everypage" style="display:none">
# <p style="float:right"><a href="#top" onclick="$.cookie(\'seen_foi2\', 1, { expires: 7, path: \'/\' }); $(\'#everypage\').hide(\'slow\'); return false;">Close</a></p>
# <h2>Blimey. It looks like the Internets won &ndash; <small>a message from TheyWorkForYou</small></h2>
# <p>Sorry to interrupt, but we thought you&rsquo;d like to know that <strong>you won</strong>!';
# echo $foi2009_message;
# echo '<p align="right"><a href="#top" onclick="$.cookie(\'seen_foi2\', 1, { expires: 7, path: \'/\' }); $(\'#everypage\').hide(\'slow\'); return false;">Close</a></p>
# </div>';

		$this->mysociety_bar();
		$this->title_bar();
		$this->menu();
	}
	
	//render the little mysociety crossell
	function mysociety_bar () {
		global $this_page;
		?>
		    <div id="mysociety_bar">
		    <?php if (1==0 && $this_page != 'overview') { ?>
	            <div id="headercampaign"><p><a href="http://www.pledgebank.com/twfypatrons">Become a They Work For You Patron ...</p></a></div>				        
	        <?php } ?>
		        <ul>
		            <li id="logo">
		                <a href="http://www.mysociety.org"><span>mySociety</span></a>
		            </li>
		            <li>
		                <a href="http://www.mysociety.org/donate/?cs=1" title="Like this website? Dontate to help keep it running.">Donate</a>
		            </li>
		            <li id="moresites">
		                <a id="moresiteslink" href="http://www.mysociety.org/projects/?cs=1" title="Donate to UK Citizens Online Democracy, mySociety's parent charity.">More</a>
		            </li>
		            <li >
                        <noscript>

                            <a href="http://www.mysociety.org/projects/?cs=1" title="View all mySociety's projects">More mySociety projects...</a>&nbsp;&nbsp;
                            <a href="https://secure.mysociety.org/admin/lists/mailman/listinfo/news?cs=1" title="mySociety newsletter - about once a month">mySociety newsletter</a>                
                        </noscript>		              
		            </li>
		        </ul>
		    </div>
		
		<?php	    
    }
	
	function title_bar () {
		// The title bit of the page, with possible search box.
		global $this_page, $DATA;
		
		$img = '<img src="' . IMAGEPATH . 'logo.png" width="423" height="80" alt="TheyWorkForYou.com">';

		if ($this_page != 'home') {
			$HOMEURL = new URL('home');
			$HOMEURL = $HOMEURL->generate();
			$HOMETITLE = 'To the front page of the site';
			$img = '<a href="' . $HOMEURL . '" title="' . $HOMETITLE . '">' . $img . '</a>';
		}

/*
XXX: Confusing, I don't like it, we have the filter now, so don't have this for the moment.

		# As in menu(), we work out what section of the site we're in
		$this_parent = $DATA->page_metadata($this_page, 'parent');
		if (!$this_parent) {
			$top_hilite = $this_page;
		} else {
			$parents_parent = $DATA->page_metadata($this_parent, 'parent');
			$top_hilite = $parents_parent ? $parents_parent : $this_parent;
		}
		if ($top_hilite == 'hansard') {
			$section = 'uk';
		} elseif ($top_hilite == 'ni_home') {
			$section = 'ni';
		} elseif ($top_hilite == 'sp_home') {
			$section = 'scotland';
		} else {
			$section = '';
		}
*/
		?>
	<div id="banner">
		<div id="title">
			<h1><?php echo $img; ?></h1>
		</div>
<?php
	#		if ($this_page != 'home' && $this_page != 'search' && $this_page != 'yourmp') {
			$URL = new URL('search');
			$URL->reset();
			?>
		<div id="search">
			<form action="<?php echo $URL->generate(); ?>" method="get">
			   <label for="searchbox">Search</label><input id="searchbox" name="s" size="15">
			   <input type="submit" class="submit" value="Go">
			   <? /* <input type="hidden" name="section" value="<?=$section?>"> */ ?>
			</form>
			<ul>
			    <li>
			        e.g. a <em>word</em>, <em>phrase</em> or <em>person</em>
			    </li>
			    <li>
			        |
			    </li>
			    <li>
			        <a href="/search/">More options</a>
			    </li>			    
		    </ul>
		</div>
<?php
	#		}
		?>
	</div> <!-- end #banner -->
<?php	
	}
	
	function menu () {
		global $this_page, $DATA, $THEUSER;

		// Page names mapping to those in metadata.php.
		// Links in the top menu, and the sublinks we see if
		// we're within that section.
		$items = array (
			array('home'),
			array('hansard', 'overview', 'mps', 'peers', 'alldebatesfront', 'wranswmsfront', 'pbc_front'),
			array('sp_home', 'spoverview', 'msps', 'spdebatesfront', 'spwransfront'),
			array('ni_home', 'nioverview', 'mlas'),
			array('wales_home'),
		);

		$top_links = array();
		$bottom_links = array();
		
		// We work out which of the items in the top and bottom menus
		// are highlighted - $top_hilite and $bottom_hilite respectively.
		
		$parent = $DATA->page_metadata($this_page, 'parent');
		
		if (!$parent) {
			// This page is probably one of the ones in the top men.
			// So hilite it and no bottom menu hilites.
			$top_hilite = $this_page;
			$bottom_hilite = '';
		
			$selected_top_link = $DATA->page_metadata('hansard', 'menu');
			$url = new URL('hansard');
			$selected_top_link['link'] = $url->generate();

		} else {

			$parents = array($parent);
			$p = $parent;
			while ($p) {
				$p = $DATA->page_metadata($p, 'parent');
				if ($p) $parents[] = $p;
			}

			$top_hilite = array_pop($parents);
			if (!$parents) {
				// No grandparent - this page's parent is in the top menu.
				// We're on one of the pages linked to by the bottom menu.
				// So hilite it and its parent.
				$bottom_hilite = $this_page;
			} else {
				// This page is not in either menu. So hilite its parent
				// (in the bottom menu) and its grandparent (in the top).
				$bottom_hilite = array_pop($parents);
			}

			$selected_top_link = $DATA->page_metadata($top_hilite, 'menu');
			if (!$selected_top_link) {
				# Just in case something's gone wrong
				$selected_top_link = $DATA->page_metadata('hansard', 'menu');
			}
			$url = new URL($top_hilite);
			$selected_top_link['link'] = $url->generate();

		}

        //get the top and bottom links
		foreach ($items as $bottompages) {
			$toppage = array_shift($bottompages);
			
			// Generate the links for the top menu.

			// What gets displayed for this page.
			$menudata = $DATA->page_metadata($toppage, 'menu');			
    			$text = $menudata['text'];
    			$title = $menudata['title'];
			if (!$title) continue;

                //get link and description for the menu ans add it to the array
    			$class = $toppage == $top_hilite ? ' class="on"' : '';            
    			$URL = new URL($toppage);			            
    			$top_link = array("link" => '<a href="' . $URL->generate() . '" title="' . $title . '"' . $class . '>' . $text . '</a>', 
    			    "title" => $title);
                array_push($top_links, $top_link);
            
			if ($toppage == $top_hilite) {
 
				// This top menu link is highlighted, so generate its bottom menu.
				foreach ($bottompages as $bottompage) {
					$menudata = $DATA->page_metadata($bottompage, 'menu');					
					$text = $menudata['text'];
					$title = $menudata['title'];
					// Where we're linking to.
					$URL = new URL($bottompage);	
					$class = $bottompage == $bottom_hilite ? ' class="on"' : '';
					$bottom_links[] = '<a href="' . $URL->generate() . '" title="' . $title . '"' . $class . '>' . $text . '</a>';
				}
			}
		}
		?>
	<div id="menu">
		<div id="topmenu">
		    <div id="topmenuselected"><a href="<?=$selected_top_link['link']?>"><?=$selected_top_link['text'] ?></a> <a id="topmenu-change" href="/parliaments/" onclick="toggleVisible('site');return false;"><small>(change)</small></a></div>
<?php
			$this->user_bar($top_hilite);
			?>
    			<dl id="site">
    			    <?php foreach ($top_links as $top_link) {?>
            			<dt><?php print $top_link['link']; ?></dt>
            			<dd><?php print $top_link['title']; ?></dd>
    			    <?php } ?>
    			</dl>

			<br>
		</div>
		<div id="bottommenu">
			<ul>
			<li><?php print implode("</li>\n\t\t\t<li>", $bottom_links); ?></li>
			</ul>
		</div>
	</div> <!-- end #menu -->

<?php 
	}


	function user_bar ($top_hilite='') {
		// Called from menu(), but separated out here for clarity.
		// Does just the bit of the menu related to login/join/etc.
		global $this_page, $DATA, $THEUSER;

		// We may want to send the user back to this current page after they've
		// joined, logged out or logged in. So we put the URL in $returl.
		$URL = new URL($this_page);
		$returl = $URL->generate('none');

		//user logged in
		if ($THEUSER->isloggedin()) {
		
			// The 'Edit details' link.
			$menudata 	= $DATA->page_metadata('userviewself', 'menu');
			$edittext 	= $menudata['text'];
			$edittitle 	= $menudata['title'];
			$EDITURL 	= new URL('userviewself');
			if ($this_page == 'userviewself' || $this_page == 'useredit' || $top_hilite == 'userviewself') {
				$editclass = ' class="on"';
			} else {
				$editclass = '';
			}

			// The 'Log out' link.
			$menudata 	= $DATA->page_metadata('userlogout', 'menu');
			$logouttext	= $menudata['text'];
			$logouttitle= $menudata['title'];
			
			$LOGOUTURL	= new URL('userlogout');
			if ($this_page != 'userlogout') {
				$LOGOUTURL->insert(array("ret"=>$returl));
				$logoutclass = '';
			} else {
				$logoutclass = ' class="on"';
			}
			
			$username = $THEUSER->firstname() . ' ' . $THEUSER->lastname();

		?>
		    
			<ul id="user">
			<li><a href="<?php echo $LOGOUTURL->generate(); ?>" title="<?php echo $logouttitle; ?>"<?php echo $logoutclass; ?>><?php echo $logouttext; ?></a></li>
			<li><a href="<?php echo $EDITURL->generate(); ?>" title="<?php echo $edittitle; ?>"<?php echo $editclass; ?>><?php echo $edittext; ?></a></li>
			<li><span class="name"><?php echo htmlentities($username); ?></span></li>
<?php

		} else {
		// User not logged in

			// The 'Join' link.
			$menudata 	= $DATA->page_metadata('userjoin', 'menu');
			$jointext 	= $menudata['text'];
			$jointitle 	= $menudata['title'];

			$JOINURL 	= new URL('userjoin');
			if ($this_page != 'userjoin') {
				if ($this_page != 'userlogout' && $this_page != 'userlogin') {
					// We don't do this on the logout page, because then the user
					// will return straight to the logout page and be logged out
					// immediately!
					$JOINURL->insert(array("ret"=>$returl));	
				}
				$joinclass = '';
			} else {
				$joinclass = ' class="on"';
			}

			// The 'Log in' link.
			$menudata 	= $DATA->page_metadata('userlogin', 'menu');
			$logintext 	= $menudata['text'];
			$logintitle	= $menudata['title'];
			
			$LOGINURL 	= new URL('userlogin');
			if ($this_page != 'userlogin') {
				if ($this_page != "userlogout" && 
					$this_page != "userpassword" && 
					$this_page != 'userjoin') {
					// We don't do this on the logout page, because then the user
					// will return straight to the logout page and be logged out
					// immediately!
					// And it's also silly if we're sent back to Change Password.
					// And the join page.
					$LOGINURL->insert(array("ret"=>$returl));
				}
				$loginclass = '';
			} else {
				$loginclass = ' class="on"';
			}
		
		?>
			<ul id="user">
			<li><a href="<?php echo $LOGINURL->generate(); ?>" title="<?php echo $logintitle; ?>"<?php echo $loginclass; ?>><?php echo $logintext; ?></a></li>
			<li><a href="<?php echo $JOINURL->generate(); ?>" title="<?php echo $jointitle; ?>"<?php echo $joinclass; ?>><?php echo $jointext; ?></a></li>
<?php
		}

		// If the user's postcode is set, then we add a link to Your MP etc.
		$divider = true;
		if ($THEUSER->postcode_is_set()) {
			$items = array('yourmp');
			if (postcode_is_scottish($THEUSER->postcode()))
				$items[] = 'yourmsp';
			elseif (postcode_is_ni($THEUSER->postcode()))
				$items[] = 'yourmla';
			foreach ($items as $item) {
				$menudata 	= $DATA->page_metadata($item, 'menu');
				$logintext 	= $menudata['text'];
				$logintitle	= $menudata['title'];
				$URL = new URL($item);
				if($divider){
				    echo '<li class="divider"><a href="' . $URL->generate() . '">' . $logintext . '</a></li>';				    
			    }else{
				    echo '<li><a href="' . $URL->generate() . '">' . $logintext . '</a></li>';			        
		        }
				$divider = false;
			}
		}
		echo '</ul>';
	}


	// Where the actual meat of the page begins, after the title and menu.
	function content_start () {
		global $this_page;
		echo '<div id="content">';
		return; # Not doing survey yet
		if (in_array($this_page, array('survey', 'overview'))) {
			return;
		}
		$show_survey_qn = 0;
		if (isset($_COOKIE['survey'])) {
			$show_survey_qn = $_COOKIE['survey'];
		} else {
			$rand = rand(1, 100);
			if ($rand >= 1) {
				$show_survey_qn = 1;
			}
			setcookie('survey', $show_survey_qn, time()+60*60*24*365, '/');
		}
		if ($show_survey_qn == 1) {
			echo '
<div id="survey_teaser">Did you find what you were looking for?
<br><a href="/survey/?answer=yes">Yes</a> | <a href="/survey/?answer=no">No</a>
</div>';
		}
	}


	function stripe_start ($type='side', $id='', $extra_class = '') {
		// $type is one of:
		//	'full' - a full width div
		// 	'side' - a white stripe with a coloured sidebar.
		//           (Has extra padding at the bottom, often used for whole pages.)
		//  'head-1' - used for the page title headings in hansard.
		//	'head-2' - used for section/subsection titles in hansard.
		// 	'1', '2' - For alternating stripes in listings.
		//	'time-1', 'time-2' - For displaying the times in hansard listings.
		// 	'procedural-1', 'procedural-2' - For the proecdures in hansard listings.
		//	'foot' - For the bottom stripe on hansard debates/wrans listings.
		// $id is the value of an id for this div (if blank, not used).
		?>
		<div class="stripe-<?php echo $type; ?><?php if ($extra_class != '') echo ' ' . $extra_class; ?>"<?php
		if ($id != '') {
			print ' id="' . $id . '"';
		}
		?>>
			<div class="main">
<?php
		$this->within_stripe_main = true;
		// On most, uncomplicated pages, the first stripe on a page will include
		// the page heading. So, if we haven't already printed a heading on this
		// page, we do it now...
		if (!$this->heading_displayed() && $this->supress_heading != true) {
			$this->heading();
		}
	}


	function stripe_end ($contents = array(), $extra = '') {
		// $contents is an array containing 0 or more hashes.
		// Each hash has two values, 'type' and 'content'.
		// 'Type' could be one of these:
		//	'include' - will include a sidebar named after the value of 'content'.php.
		//	'nextprev' - $this->nextprevlinks() is called ('content' currently ignored).
		//	'html' - The value of the 'content' is simply displayed.
		//	'extrahtml' - The value of the 'content' is displayed after the sidebar has
		//					closed, but within this stripe.

		// If $contents is empty then '&nbsp;' will be output.
		
		/* eg, take this hypothetical array:
			$contents = array(
				array (
					'type'	=> 'include',
					'content'	=> 'mp'
				),
				array (
					'type'	=> 'html',
					'content'	=> "<p>This is your MP</p>\n"
				),
				array (
					'type'	=> 'nextprev'
				),
    			array (
    				'type'	=> 'none'
    			),				
				array (
					'extrahtml' => '<a href="blah">Source</a>'
				)
			);
		
			The sidebar div would be opened.
			This would first include /includes/easyparliament/templates/sidebars/mp.php.
			Then display "<p>This is your MP</p>\n".
			Then call $this->nextprevlinks().
			The sidebar div would be closed.
			'<a href="blah">Source</a>' is displayed.
			The stripe div is closed.
			
			But in most cases we only have 0 or 1 hashes in $contents.
		
		*/
		
		// $extra is html that will go after the sidebar has closed, but within
		// this stripe.
		// eg, the 'Source' bit on Hansard pages.
		global $DATA, $this_page;

		$this->within_stripe_main = false;
		?>
			</div> <!-- end .main -->
			<div class="sidebar">

        <? 
		$this->within_stripe_sidebar = true;
		$extrahtml = '';
		
		if (count($contents) == 0) {
			print "\t\t\t&nbsp;\n";
		} else {
			#print '<div class="sidebar">';
			foreach ($contents as $hash) {
				if (isset($hash['type'])) {
					if ($hash['type'] == 'include') {
						$this->include_sidebar_template($hash['content']);
					
					} elseif ($hash['type'] == 'nextprev') {
						$this->nextprevlinks();
					
					} elseif ($hash['type'] == 'html') {
						print $hash['content'];
					
					} elseif ($hash['type'] == 'extrahtml') {
						$extrahtml .= $hash['content'];
					}
				}

			}
		}

		$this->within_stripe_sidebar = false;
		?>
			</div> <!-- end .sidebar -->
			<div class="break"></div>
<?php
		if ($extrahtml != '') {
			?>
			<div class="extra"><?php echo $extrahtml; ?></div>
<?php
			}
			?>
		</div> <!-- end .stripe-* -->
		
<?php
	}



	function include_sidebar_template ($sidebarname) {
		global $this_page, $DATA;
		
			$sidebarpath = INCLUDESPATH.'easyparliament/sidebars/'.$sidebarname.'.php';

			if (file_exists($sidebarpath)) {
				include $sidebarpath;
			}
	}
	
	
	function block_start($data=array()) {
		// Starts a 'block' div, used mostly on the home page,
		// on the MP page, and in the sidebars.
		// $data is a hash like this:
		//	'id'	=> 'help',	
		//	'title'	=> 'What are debates?'
		//	'url'	=> '/help/#debates' 	[if present, will be wrapped round 'title']
		//	'body'	=> false	[If not present, assumed true. If false, no 'blockbody' div]
		// Both items are optional (although it'll look odd without a title).

		$this->blockbody_open = false;
		
		if (isset($data['id']) && $data['id'] != '') {
			$id = ' id="' . $data['id'] . '"';
		} else {
			$id = '';
		}
		
		$title = isset($data['title']) ? $data['title'] : '';
		
		if (isset($data['url'])) {
			$title = '<a href="' . $data['url'] . '">' . $title . '</a>';
		}
		?>
				<div class="block"<?php echo $id; ?>>
					<h4><?php echo $title; ?></h4>
<?php
		if (!isset($data['body']) || $data['body'] == true) {
			?>
					<div class="blockbody">
<?php
			$this->blockbody_open = true;
			}
	}
	
	
	function block_end () {
		if ($this->blockbody_open) {
			?>
					</div>
<?php
			}
			?>
				</div> <!-- end .block -->	

<?php
	}
	

	function heading() {
		global $this_page, $DATA;

		// As well as a page's title, we may display that of its parent.
		// A page's parent can have a 'title' and a 'heading'.
		// The 'title' is always used to create the <title></title>.
		// If we have a 'heading' however, we'll use that here, on the page, instead.

		$parent_page = $DATA->page_metadata($this_page, 'parent');
	
		if ($parent_page != '') {
			// Not a top-level page, so it has a section heading.
			// This is the page title of the parent.
			$section_text = $DATA->page_metadata($parent_page, 'title');
			
		} else {
			// Top level page - no parent, hence no parental title.
			$section_text = '';
		}
		
		
		// A page can have a 'title' and a 'heading'.
		// The 'title' is always used to create the <title></title>.
		// If we have a 'heading' however, we'll use that here, on the page, instead.
		
		$page_text = $DATA->page_metadata($this_page, "heading");

		if ($page_text == '' && !is_bool($page_text)) {
			// If the metadata 'heading' is set, but empty, we display nothing.
		} elseif ($page_text == false) {
			// But if it just hasn't been set, we use the 'title'.
			$page_text = $DATA->page_metadata($this_page, "title");
		}
		
		if ($page_text == $section_text) {
			// We don't want to print both.
			$section_text = '';
		} elseif (!$page_text && $section_text) {
			// Bodge for if we have a page_text but no section_text.
			$section_text = '';
			$page_text = $section_text;
		}

		# XXX Yucky
		if ($this_page != 'home' && $this_page != 'contact') {
			if ($page_text) {
				print "\t\t\t\t<h2>$page_text</h2>\n";
			}
			if ($section_text && $parent_page != 'help_us_out' && $parent_page != 'home' && $this_page != 'campaign') {
				print "\t\t\t\t<h3>$section_text</h3>\n";
			}
		}

		// So we don't print the heading twice by accident from $this->stripe_start().
		$this->heading_displayed = true;
	}



	

	function content_end () {

	    print "</div> <!-- end #content -->";

	}

    //get <a> links for a particular set of pages defined in metadata.php
	function get_menu_links ($pages){
		global $DATA, $this_page;
		$links = array();
		
		foreach ($pages as $page) {

            //get meta data
			$title = $DATA->page_metadata($page, 'title');
			$url = $DATA->page_metadata($page, 'url');
			
			//check for external vs internal menu links
			if(!valid_url($url)){
			    $URL = new URL($page);
			    $url = $URL->generate();
	        }
	        
			//make the link
			if ($page == $this_page) {
				$links[] = $title;
			} else {
				$links[] = '<a href="' . $url . '" title="' . $title . '">' . $title . '</a>';
			}
		}

		return $links;
    }

	function page_footer ($extra = null) {
		global $DATA, $this_page;

        		global $DATA, $this_page;

        		$about_links = $this->get_menu_links(array ('help', 'about', 'linktous', 'houserules', 'blog', 'news', 'contact'));
                $assembly_links = $this->get_menu_links(array ('hansard', 'sp_home', 'ni_home', 'wales_home'));		
                $international_links = $this->get_menu_links(array ('newzealand', 'australia', 'ireland'));
                $tech_links = $this->get_menu_links(array ('code', 'api', 'data', 'devmailinglist', 'irc'));

        /*
        		$about_links[] = '<a href="' . WEBPATH . 'api/">API</a> / <a href="http://ukparse.kforge.net/parlparse">XML</a>';
        		$about_links[] = '<a href="https://secure.mysociety.org/cvstrac/dir?d=mysociety/twfy">Source code</a>';

        		$user_agent = ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) ? strtolower( $_SERVER['HTTP_USER_AGENT'] ) : '';
        		if (stristr($user_agent, 'Firefox/'))
        			$about_links[] = '<a href="http://mycroft.mozdev.org/download.html?name=theyworkforyou">Add search to Firefox</a>';

        */			
        		?>

        		<div id="footer">
        			<dl>
        			    <dt>About: </dt>
        			    <dd>
        			        <ul>
                			    <?php
                			        foreach ($about_links as $about_link) {
                			            echo '<li>' . $about_link . '</li>';
                			        }
                                ?>
                            </ul>
                        </dd>
        			    <dt>Parliaments &amp; assemblies: </dt>
        			    <dd>
        			        <ul>
                			    <?php
                			        foreach ($assembly_links as $assembly_link) {
                			            echo '<li>' . $assembly_link . '</li>';
                			        }
                                ?>
                            </ul>
                        </dd>
        			    <dt>International projects: </dt>
        			    <dd>
        			        <ul>
                			    <?php
                			        foreach ($international_links as $international_link) {
                			            echo '<li>' . $international_link . '</li>';
                			        }
                                ?>
                            </ul>
                        </dd>                
                        <dt>Technical: </dt>
        			    <dd>
        			        <ul>
                			    <?php
                			        foreach ($tech_links as $tech_link) {
                			            echo '<li>' . $tech_link . '</li>';
                			        }
                                ?>
                            </ul>
                        </dd>
        		  </dl>
        		  <div>
        		      <h5>Donate</h5>
        		      <p>
        		          This website is run by <a href="http://www.mysociety.org/">mySociety</a>, a registered charity.
				  If you find it useful, please <a href="http://www.mysociety.org/donate/">donate</a> to keep it running.
        		      </p>
        		      <h5>Sign up to our newsletter</h5>
        		      <form method="get" action="https://secure.mysociety.org/admin/lists/mailman/subscribe/news">
        		          <input type="text" name="email">
        		          <input type="submit" value="Join">
        		      </form>
        		      <p>
        		          Approximately once a month, spam free.
        		      </p>
        		  </div>
        <?php
		// This makes the tracker appear on all sections, but only actually on theyworkforyou.com
				//if ($DATA->page_metadata($this_page, 'track') ) {
		if (DOMAIN == 'www.theyworkforyou.com') {
					// We want to track this page.
			// Kind of fake URLs needed for the tracker.
			$url = urlencode('http://' . DOMAIN . '/' . $this_page);
			?>
<script type="text/javascript"><!--
an=navigator.appName;sr='http://x3.extreme-dm.com/';srw="na";srb="na";d=document;r=41;function pr(n) {
d.write("<img alt='' src=\""+sr+"n\/?tag=fawkes&p=<?php echo $url; ?>&j=y&srw="+srw+"&srb="+srb+"&l="+escape(d.referrer)+"&rs="+r+"\" height='1' width='1'>");}
s=screen;srw=s.width;an!="Netscape"?srb=s.colorDepth:srb=s.pixelDepth
pr()//-->
</script><noscript><img alt="" src="http://x3.extreme-dm.com/z/?tag=fawkes&amp;p=<?php echo $url; ?>&amp;j=n" height="1" width="1"></noscript>
<?php

			// mySociety tracking, not on staging
			if (defined('OPTION_TRACKING') && OPTION_TRACKING) {
		                track_event($extra);
			}
		}

		// DAMN, this really shouldn't be in PAGE.
		$db = new ParlDB;
		$db->display_total_duration();
		
		$duration = getmicrotime() - STARTTIME;
		twfy_debug ("TIME", "Total time for page: $duration seconds.");
		if (!isset($_SERVER['WINDIR'])) {
			$rusage = getrusage();
			$duration = $rusage['ru_utime.tv_sec']*1000000 + $rusage['ru_utime.tv_usec'] - STARTTIMEU;
			twfy_debug ('TIME', "Total user time: $duration microseconds.");
			$duration = $rusage['ru_stime.tv_sec']*1000000 + $rusage['ru_stime.tv_usec'] - STARTTIMES;
			twfy_debug ('TIME', "Total system time: $duration microseconds.");
		}
		
		if (DOMAIN == 'www.theyworkforyou.com') { ?>
<!-- Piwik -->
<script type="text/javascript">
var pkBaseURL = (("https:" == document.location.protocol) ? "https://piwik.mysociety.org/" : "http://piwik.mysociety.org/");
document.write(unescape("%3Cscript src='" + pkBaseURL + "piwik.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
<!--
piwik_action_name = '';
piwik_idsite = 7;
piwik_url = pkBaseURL + "piwik.php";
piwik_log(piwik_action_name, piwik_idsite, piwik_url);
//-->
</script>
<noscript><img src="http://piwik.mysociety.org/piwik.php?i=1" width=1 height=1 style="border:0" alt=""></noscript>
<!-- /Piwik -->
<?
		}
?>

<br class="clear"/>
</div> <!-- end #footer -->
</div> <!-- end #container -->

<script type="text/javascript" charset="utf-8">
    barSetup();
</script>
</body>
</html>
<?php
		ob_end_flush();
	}

	function postcode_form() {
		// Used on the mp (and yourmp) pages.
		// And the userchangepc page.
		global $THEUSER;
		
		echo '<br>';
		$this->block_start(array('id'=>'mp', 'title'=>'Find out about your MP/MSPs/MLAs'));
		echo '<form action="/postcode/" method="get">';
		if (get_http_var('c4')) print '<input type="hidden" name="c4" value="1">';
		if (get_http_var('c4x')) print '<input type="hidden" name="c4x" value="1">';
		if ($THEUSER->postcode_is_set()) {
			$FORGETURL = new URL('userchangepc');
			$FORGETURL->insert(array('forget'=>'t'));
			?>
						<p>Your current postcode: <strong><?php echo $THEUSER->postcode(); ?></strong> &nbsp; <small>(<a href="<?php echo $FORGETURL->generate(); ?>" title="The cookie storing your postcode will be erased">Forget this postcode</a>)</small></p>
<?php
		}
		?>
						<p><strong>Enter your UK postcode: </strong>

						<input type="text" name="pc" value="<?php echo htmlentities(get_http_var('pc')); ?>" maxlength="10" size="10"> <input type="submit" value="GO" class="submit"> <small>(e.g. BS3 1QP)</small>
						</p>
						<input type="hidden" name="ch" value="t">
						</form>
<?php	
		$this->block_end();
	}

	function member_rss_block ($urls) {
		// Returns the html for a person's rss feeds sidebar block.
		// Used on MP/Peer page.
		
		$html = '
				<div class="block">
				<h4>RSS feeds</h4>
					<div class="blockbody">
						<ul>
';
		if (isset($urls['appearances'])) {
			$html .= '<li><a href="' . $urls['appearances'] . '"><img src="' . WEBPATH . 'images/rss.gif" alt="RSS feed" border="0" align="middle"></a> <a href="' . $urls['appearances'] . '">Most recent appearances</a></li>';
		}
		
		$HELPURL = new URL('help');
			
		$html .= '
						</ul>
						<p><a href="' . $HELPURL->generate() . '#rss" title="An explanation of what RSS feeds are for"><small>What is RSS?</small></a></p>
					</div>
				</div>
';
		return $html;

	}
	

	function display_member($member, $extra_info) {
		global $THEUSER, $DATA, $this_page;

		#$title = ucfirst($member['full_name']);

/*		if (isset($extra_info["public_whip_dreammp996_distance"])) {
			$dmpscore = floatval($extra_info["public_whip_dreammp996_distance"]);
			$strongly_foi = "voted " . score_to_strongly(1.0 - $dmpscore);
		}
		if ($extra_info["public_whip_dreammp996_both_voted"] == 0) {
			$strongly_foi = "has never voted on";
		}
		$this->block_start(array('id'=>'black', 'title'=>"Freedom of Information and Parliament"));
		print "<p>There is currently a Bill before Parliament which will make Parliament
			exempt from Freedom of Information requests. This Bill will remove
			your legal right to see some of the information on this page, notably
			expenses, replacing it with a weaker promise that could be retracted
			later.</p>

			<p>Even if this bill is amended to exclude expenses, exemption from the
			Freedom of Information Act may prevent TheyWorkForYou from adding
			useful information of new sorts in the future. The Bill is not backed
			or opposed by a specific party, and TheyWorkForYou remains strictly
			neutral on all issues that do not affect our ability to serve the
			public.</p>

			<p><a href=\"somewhere\">Join the Campaign to keep Parliament transparent
			(external)</a>.</p>";

		print 'For your information, '.$title.' MP <a href="http://www.publicwhip.org.uk/mp.php?mpid='.$member['member_id'].'&amp;dmp=996">'.$strongly_foi.'</a> this Bill.';
		$this->block_end();
*/

    if ( (isset($extra_info["is_speaker_candidate"]) && $extra_info["is_speaker_candidate"] == 1  && isset($extra_info["speaker_candidate_contacted_on"]))
        || (isset($extra_info['speaker_candidate_response']) && $extra_info['speaker_candidate_response']) ) {

	$just_response = false;
        if ($extra_info['is_speaker_candidate'] == 0) {
		$just_response = true;
	}

      // days since originally contacted
      $contact_date_string = $extra_info["speaker_candidate_contacted_on"];
      $contact_date_midnight = strtotime($contact_date_string);
      $days_since_contact = floor((time() - $contact_date_midnight) / 86400);
      if ($days_since_contact == 1){
        $days_since_string = $days_since_contact . ' day ago';
      }elseif($days_since_contact > 1){
        $days_since_string = $days_since_contact . ' days ago';
      }else{
        $days_since_string = 'today';
      }

      $reply_time = "*unknown*";
      if (isset($extra_info["speaker_candidate_replied_on"])) {
         $reply_date_string = $extra_info["speaker_candidate_replied_on"];
         $reply_date_midnight = strtotime($reply_date_string);
         $days_for_reply = floor(($reply_date_midnight - $contact_date_midnight) / 86400);
         if ($days_for_reply == 0) {
            $reply_time = "in less than 24 hours";
         }elseif($days_for_reply == 1) {
            $reply_time = "in 1 day";
         }else{
            $reply_time = "in $days_for_reply days";
         }
      }

      if ($just_response) {
         $spk_cand_title = $member['full_name'] . ' endorses our Speaker principles';
      } else {
        if (isset($extra_info["speaker_candidate_elected"]) && $extra_info["speaker_candidate_elected"] == 1){
          $spk_cand_title = 'LATEST: ' . $member['full_name'] . ' elected Speaker. Here\'s what he endorsed:';
        }else{
         $spk_cand_title = 'IMPORTANT: ' . $member['full_name'] . ' was a Candidate for Speaker.';
        }
      }
      $this->block_start(array('id'=>'campaign_block', 'title' => $spk_cand_title));

      if (!isset($extra_info["speaker_candidate_response"])){
          print "
                You can help make sure that all the candidates understand that they
                must be a strong, Internet-savvy proponents of a better, more
                accountable era of democracy.";
      }
      print "</p>

            <p>mySociety asked likely candidates for the post of Speaker to endorse the
            following principles." ;
     
      print "<p><strong>The three principles are:</strong></p>

            <ol>

               <li> Voters have the right to know in <strong>detail about the money</strong> that is spent to
            support MPs and run Parliament, and in similar detail how the decisions to
            spend that money are settled upon. </li>

               <li> Bills being considered must be published online in a much better way than
            they are now, as the <strong>Free Our Bills</strong> campaign has been suggesting for some time. </li>

               <li> The Internet is not a threat to a renewal in our democracy, it is one of
            its best hopes. Parliament should appoint a senior officer with direct working
            experience of the <strong>power of the Internet</strong> who reports directly to the Speaker,
            and who will help Parliament adapt to a new era of transparency and
            effectiveness. </li>

            </ol>";

    if (isset($extra_info["speaker_candidate_response"]) && $extra_info["speaker_candidate_response"]){
         print "</p><p><strong><big>Update: " . $member['full_name'] . " MP replied $reply_time. " . $extra_info["speaker_candidate_response_summary"] . " Here's the reply in full: </big></strong></p>";
         print "<blockquote><div id='speaker_candidate_response'>";
         print $extra_info["speaker_candidate_response"];
         print "</div></blockquote>";
    }else{
         print "<p> We contacted " . $member['full_name'] . " MP to ask for an endorsement " . $days_since_string . ". ";
    	 print "They have not yet replied.</p>";
    }
      $this->block_end();
    }


        $is_lord = false;
		foreach ($member['houses'] as $house) {
			if ($house==2) $is_lord = true; continue;
			if (!$member['current_member'][$house]) $title .= ', former';
			if ($house==1) $title .= ' MP';
			if ($house==3) $title .= ' MLA';
			if ($house==4) $title .= ' MSP';
		}
		#if ($rssurl = $DATA->page_metadata($this_page, 'rss')) {
		#	$title = '<a href="' . WEBPATH . $rssurl . '"><img src="' . WEBPATH . 'images/rss.gif" alt="RSS feed" border="0" align="right"></a> ' . $title;
		#}


		print '<p class="printonly">This data was produced by TheyWorkForYou from a variety of sources.</p>';


        //get the correct image (with special case for lords)
        if($is_lord){
		    list($image,$sz) = find_rep_image($member['person_id'], false, 'lord');
		}else{
		    list($image,$sz) = find_rep_image($member['person_id'], false, true);		    
	    }
	    
	    //show image	    
	    echo '<p class="person">';
		echo '<img class="portrait" alt="Photo of ', $member['full_name'], '" src="', $image, '"';
		if ($sz=='S') echo ' height="118"';
		echo '></p>';

        
        //work out person's description
		$desc = '';
		$last_item = end($member['houses']);
		foreach ($member['houses'] as $house) {
			if ($house==0) {
				$desc .= '<li><strong>Acceded on ';
				$desc .= $member['entered_house'][0]['date_pretty'];
				$desc .= '</strong></li>';
				$desc .= '<li><strong>Coronated on 2 June 1953</strong></li>';
				continue;
			}
			$party = $member['left_house'][$house]['party'];
			if ($house==1 && isset($member['entered_house'][2])) continue; # Same info is printed further down

			if (!$member['current_member'][$house]) $desc .= 'Former ';
			$party_br = '';
			if (preg_match('#^(.*?)\s*\((.*?)\)$#', $party, $m)) {
				$party_br = $m[2];
				$party = $m[1];
			}
			if ($party != 'unknown')
				$desc .= htmlentities($party);
			if ($party=='Speaker' || $party=='Deputy Speaker') {
				$desc .= ', and ';
				# XXX: Will go horribly wrong if something odd happens
				if ($party=='Deputy Speaker') {
					$last = end($member['other_parties']);
					$desc .= $last['from'] . ' ';
				}
			}			
			if ($house==1 || $house==3 || $house==4) {
				$desc .= ' ';
				if ($house==1) $desc .= 'MP';
				if ($house==3) $desc .= 'MLA';
				if ($house==4) $desc .= 'MSP';
				if ($party_br) {
					$desc .= " ($party_br)";
				}

				$desc .= ' for ' . $member['left_house'][$house]['constituency'];
			}
			if ($house==2 && $party != 'Bishop') $desc .= ' Peer';
			
			if($house != $last_item){
			    $desc .= ', ';
		    }
		}

        //headings
        echo '<h2>' . $member['full_name'] . '</h2>';
        echo '<h3>' . $desc . '</h3>';


        //History
		echo '<ul class="hilites">';		
		if ($member['other_constituencies']) {
			print "<li>Also represented " . join('; ', array_keys($member['other_constituencies']));
			print '</li>';
		}

		if ($member['other_parties'] && $member['party'] != 'Speaker' && $member['party']!='Deputy Speaker') {
			print "<li>Changed party ";
			foreach ($member['other_parties'] as $r) {
				$out[] = 'from ' . $r['from'] . ' on ' . format_date($r['date'], SHORTDATEFORMAT);
			}
			print join('; ', $out);
			
			print '</li>';
		}

		// Ministerial position
		if (array_key_exists('office', $extra_info)) {
			$mins = array();
			foreach ($extra_info['office'] as $row) {
				if ($row['to_date'] == '9999-12-31' && $row['source'] != 'chgpages/selctee') {
					$m = prettify_office($row['position'], $row['dept']);
					$m .= ' (since ' . format_date($row['from_date'], SHORTDATEFORMAT) . ')';
					$mins[] = $m;
				}
			}
			if ($mins) {
				print '<li>' . join('<br>', $mins) . '</li>';
			}
		}
        echo '<br class="clear">';
		//if dummy image, show message asking for a photo
		if(!exists_rep_image($member['person_id'])){

			// For MPs, prompt for photo
			echo '<p class="missingphoto">';
			if($member['current_member_anywhere']){
			    echo 'Help, we\'re missing a photo of ' . $member['full_name'] . '! If you are ' . $member['full_name'] . ', or have a photo of them (that you have copyright of) <a href="mailto:team@theyworkforyou.com">please email it to us</a>.';
			}else{
			    echo 'Help, we\'re missing a photo of ' . $member['full_name'] . '! If have a photo of them (that you have copyright of), or can locate a copyright free photo of them <a href="mailto:team@theyworkforyou.com">please email it to us</a>.';			    
		    }
		    echo '</p>';
		}
		if (isset($member['left_house'][1]) && isset($member['entered_house'][2])) {
			print '<li><strong>Entered the House of Lords ';
			if (strlen($member['entered_house'][2]['date_pretty'])==4)
				print 'in ';
			else
				print 'on ';
			print $member['entered_house'][2]['date_pretty'].'</strong>';
			print '</strong>';
			if ($member['entered_house'][2]['reason']) print ' &mdash; ' . $member['entered_house'][2]['reason'];
			print '</li>';
			print '<li><strong>Previously MP for ';
			print $member['left_house'][1]['constituency'] . ' until ';
			print $member['left_house'][1]['date_pretty'].'</strong>';
			if ($member['left_house'][1]['reason']) print ' &mdash; ' . $member['left_house'][1]['reason'];
			print '</li>';
		} elseif (isset($member['entered_house'][2]['date'])) {
			print '<li><strong>Became a Lord ';
			if (strlen($member['entered_house'][2]['date_pretty'])==4)
				print 'in ';
			else
				print 'on ';
			print $member['entered_house'][2]['date_pretty'].'</strong>';
			if ($member['entered_house'][2]['reason']) print ' &mdash; ' . $member['entered_house'][2]['reason'];
			print '</li>';
		}
		if (in_array(2, $member['houses']) && !$member['current_member'][2]) {
			print '<li><strong>Left Parliament on '.$member['left_house'][2]['date_pretty'].'</strong>';
			if ($member['left_house'][2]['reason']) print ' &mdash; ' . $member['left_house'][2]['reason'];
			print '</li>';
		}

		if (isset($extra_info['lordbio'])) {
			echo '<li><strong>Positions held at time of appointment:</strong> ', $extra_info['lordbio'],
				' <small>(from <a href="',
				$extra_info['lordbio_from'], '">Number 10 press release</a>)</small></li>';
		}

		if (isset($member['entered_house'][1]['date'])) {
			print '<li><strong>Entered Parliament on ';
			print $member['entered_house'][1]['date_pretty'].'</strong>';
			if ($member['entered_house'][1]['reason']) print ' &mdash; ' . $member['entered_house'][1]['reason'];
			print '</li>';
		}
		if (in_array(1, $member['houses']) && !$member['current_member'][1] && !isset($member['entered_house'][2])) {
			print '<li><strong>Left Parliament ';
			if (strlen($member['left_house'][1]['date_pretty'])==4)
				print 'in ';
			else
				print 'on ';
			echo $member['left_house'][1]['date_pretty'].'</strong>';
			if ($member['left_house'][1]['reason']) print ' &mdash; ' . $member['left_house'][1]['reason'];
			print '</li>';
		}

		if (isset($member['entered_house'][3]['date'])) {
			print '<li><strong>Entered the Assembly on ';
			print $member['entered_house'][3]['date_pretty'].'</strong>';
			if ($member['entered_house'][3]['reason']) print ' &mdash; ' . $member['entered_house'][3]['reason'];
			print '</li>';
		}
		if (in_array(3, $member['houses']) && !$member['current_member'][3]) {
			print '<li><strong>Left the Assembly on '.$member['left_house'][3]['date_pretty'].'</strong>';
			if ($member['left_house'][3]['reason']) print ' &mdash; ' . $member['left_house'][3]['reason'];
			print '</li>';
		}
		if (isset($member['entered_house'][4]['date'])) {
			print '<li><strong>Entered the Scottish Parliament on ';
			print $member['entered_house'][4]['date_pretty'].'</strong>';
			if ($member['entered_house'][4]['reason']) print ' &mdash; ' . $member['entered_house'][4]['reason'];
			print '</li>';
		}
		if (in_array(4, $member['houses']) && !$member['current_member'][4]) {
			print '<li><strong>Left the Scottish Parliament on '.$member['left_house'][4]['date_pretty'].'</strong>';
			if ($member['left_house'][4]['reason']) print ' &mdash; ' . $member['left_house'][4]['reason'];
			print '</li>';
		}
		if (isset($extra_info['majority_in_seat'])) { 
			?>
						<li><strong>Majority:</strong> 
						<?php echo number_format($extra_info['majority_in_seat']); ?> votes. <?php

			if (isset($extra_info['swing_to_lose_seat_today'])) { 
				/*
				if (isset($extra_info['swing_to_lose_seat_today_quintile'])) {
					$q = $extra_info['swing_to_lose_seat_today_quintile'];
					if ($q == 0) {
						print 'Very safe seat';
					} elseif ($q == 1) {
						print 'Safe seat';
					} elseif ($q == 2) {
						print '';
					} elseif ($q == 3) {
						print 'Unsafe seat';
					} elseif ($q == 4) {
						print 'Very unsafe seat';
					} else {
						print '[Impossible quintile!]';
					}
				}
				*/
				print ' &mdash; ' . make_ranking($extra_info['swing_to_lose_seat_today_rank']); ?> out of <?php echo $extra_info['swing_to_lose_seat_today_rank_outof']; ?> MPs.
<?php 
			} ?></li>
<?php 
		}

		if ($member['party'] == 'Sinn Fein' && in_array(1, $member['houses'])) {
			print '<li>Sinn F&eacute;in MPs do not take their seats in Parliament</li>';
		}
		print "</ul>";
		print '<br class="clear"/>';

		
		print "<ul class=\"hilites\">";		
		if ($member['the_users_mp'] == true) {
			$pc = $THEUSER->postcode();
			?>
						<li><a href="http://www.writetothem.com/?a=WMC&amp;pc=<?php echo htmlentities(urlencode($pc)); ?>"><strong>Send a message to <?php echo $member['full_name']; ?></strong></a> (only use this for <em>your</em> MP) <small>(via WriteToThem.com)</small></li>
						<li><a href="http://www.hearfromyourmp.com/?pc=<?=htmlentities(urlencode($pc)) ?>"><strong>Get messages from your MP</strong></a> <small>(via HearFromYourMP)</small></strong></a></li>
<?php
		} elseif ($member['current_member'][1]) {
			?>
						<li><a href="http://www.writetothem.com/"><strong>Send a message to your MP</strong></a> <small>(via WriteToThem.com)</small></li>
						<li><a href="http://www.hearfromyourmp.com/"><strong>Sign up to <em>HearFromYourMP</em></strong></a> to get messages from your MP</li>
<?php
		} elseif ($member['current_member'][3]) {
			?>
						<li><a href="http://www.writetothem.com/"><strong>Send a message to your MLA</strong></a> <small>(via WriteToThem.com)</small></li>
<?php		} elseif ($member['current_member'][4]) {
			?>
						<li><a href="http://www.writetothem.com/"><strong>Send a message to your MSP</strong></a> <small>(via WriteToThem.com)</small></li>
<?php		} elseif ($member['current_member'][2]) {
			?>
						<li><a href="http://www.writetothem.com/?person=uk.org.publicwhip/person/<?php echo $member['person_id']; ?>"><strong>Send a message to <?php echo $member['full_name']; ?></strong></a> <small>(via WriteToThem.com)</small></li>
<?php

		}

		# If they're currently an MLA, a Lord or a non-Sinn Fein MP
		if ($member['current_member'][0] || $member['current_member'][2] || $member['current_member'][3] || ($member['current_member'][1] && $member['party'] != 'Sinn Fein') || $member['current_member'][4]) {
			print '<li><a href="' . WEBPATH . 'alert/?only=1&amp;pid='.$member['person_id'].'"><strong>Email me whenever '. $member['full_name']. ' speaks</strong></a> (no more than once per day)</li>';
		}

		# Video
		if ($member['current_member'][1] && $member['party'] != 'Sinn Fein') {
			echo '<li>Help us add video by <a href="/video/next.php?action=random&amp;pid=' . $member['person_id'] . '"><strong>matching a speech by ' . $member['full_name'] . '</strong></a>';
		}

		?>
						</ul>
						
						
						<ul class="jumpers hilites">
<?
		if ((in_array(1, $member['houses']) && $member['party']!='Sinn Fein') || in_array(2, $member['houses'])) {
			echo '<li><a href="#votingrecord">Voting record</a></li>';
			if ($member['current_member'][1])
				echo '<li><a href="#topics">Committees and topics of interest</a></li>';
		}
		if (!in_array(1, $member['houses']) || $member['party'] != 'Sinn Fein' || in_array(3, $member['houses']))
			echo '<li><a href="#hansard">Most recent appearances</a></li>';
		echo '<li><a href="#numbers">Numerology</a></li>';
		if (isset($extra_info['register_member_interests_html']))
			echo '<li><a href="#register">Register of Members&rsquo; Interests</a></li>';
		if (isset($extra_info['expenses2004_col1']) || isset($extra_info['expenses2006_col1']) || 
isset($extra_info['expenses2007_col1']) || isset($extra_info['expenses2008_col1']))
			echo '<li><a href="#expenses">Expenses</a></li>';

		if (isset($extra_info['edm_ais_url'])) {
			?>
						<li><a href="<?php echo $extra_info['edm_ais_url']; ?>">Early Day Motions signed by this MP</a> <small>(From edmi.parliament.uk)</small></li>
<?php
		}
		?>
						</ul>
<?php

# Big don't-print for SF MPs
$chairmens_panel = false;
if ((in_array(1, $member['houses']) && $member['party']!='Sinn Fein') || in_array(2, $member['houses'])) {

		// Voting Record.
		?> <a name="votingrecord"></a> <?php
		//$this->block_start(array('id'=>'votingrecord', 'title'=>'Voting record (from PublicWhip)'));
		print '<h4>Voting record (from PublicWhip)</h4>';
		$displayed_stuff = 0;
		function display_dream_comparison($extra_info, $member, $dreamid, $desc, $inverse, $search) {
			if (isset($extra_info["public_whip_dreammp${dreamid}_distance"])) {
				if ($extra_info["public_whip_dreammp${dreamid}_both_voted"] == 0) {
					$dmpdesc = 'Has <strong>never voted</strong> on';
				} else {
					$dmpscore = floatval($extra_info["public_whip_dreammp${dreamid}_distance"]);
					print "<!-- distance $dreamid: $dmpscore -->";
					if ($inverse) 
						$dmpscore = 1.0 - $dmpscore;
					$english = score_to_strongly($dmpscore);
					if ($extra_info["public_whip_dreammp${dreamid}_both_voted"] == 1) {
						$english = preg_replace('#(very )?(strongly|moderately) #', '', $english);
					}
					$dmpdesc = 'Voted <strong>' . $english . '</strong>';

					// How many votes Dream MP and MP both voted (and didn't abstain) in
					// $extra_info["public_whip_dreammp${dreamid}_both_voted"];
				}
				$search_link = WEBPATH . "search/?s=" . urlencode($search) . 
					"&pid=" . $member['person_id'] . "&pop=1";
				?>
				<li>
				<?=$dmpdesc?>
			<?=$desc?>. 
<small class="unneededprintlinks"> 
<a href="http://www.publicwhip.org.uk/mp.php?mpid=<?=$member['member_id']?>&amp;dmp=<?=$dreamid?>">votes</a>,
<a href="<?=$search_link?>">speeches</a>
</small>

				</li>
<?php
				return true;
			}
			return false;
		}

	if ($member['party']=='Speaker' || $member['party']=='Deputy Speaker') {
		if ($member['party']=='Speaker') $art = 'the'; else $art = 'a';
		echo "<p>As $art $member[party], $member[full_name] cannot vote (except to break a tie).</p>";
	}

	if (isset($extra_info["public_whip_dreammp230_distance"]) || isset($extra_info["public_whip_dreammp996_distance"])) { # XXX
		$displayed_stuff = 1; ?>


	<p id="howvoted">How <?=$member['full_name']?> voted on key issues since 2001:</p>
	<ul id="dreamcomparisons">
	<?
		$got_dream = false;
		$got_dream |= display_dream_comparison($extra_info, $member, 996, "a <strong>transparent Parliament</strong>", false, '"freedom of information"');
		if (in_array(1, $member['houses']))
			$got_dream |= display_dream_comparison($extra_info, $member, 811, "introducing a <strong>smoking ban</strong>", false, "smoking");
		#$got_dream |= display_dream_comparison($extra_info, $member, 856, "the <strong>changes to parliamentary scrutiny in the <a href=\"http://en.wikipedia.org/wiki/Legislative_and_Regulatory_Reform_Bill\">Legislative and Regulatory Reform Bill</a></strong>", false, "legislative and regulatory reform bill");
		$got_dream |= display_dream_comparison($extra_info, $member, 1051, "introducing <strong>ID cards</strong>", false, "id cards");
		$got_dream |= display_dream_comparison($extra_info, $member, 363, "introducing <strong>foundation hospitals</strong>", false, "foundation hospital");
		$got_dream |= display_dream_comparison($extra_info, $member, 1052, "introducing <strong>student top-up fees</strong>", false, "top-up fees");
		if (in_array(1, $member['houses']))
			$got_dream |= display_dream_comparison($extra_info, $member, 1053, "Labour's <strong>anti-terrorism laws</strong>", false, "terrorism");
		$got_dream |= display_dream_comparison($extra_info, $member, 1049, "the <strong>Iraq war</strong>", false, "iraq");
		$got_dream |= display_dream_comparison($extra_info, $member, 975, "an <strong>investigation</strong> into the Iraq war", false, "iraq");
		$got_dream |= display_dream_comparison($extra_info, $member, 984, "replacing <strong>Trident</strong>", false, "trident");
		$got_dream |= display_dream_comparison($extra_info, $member, 1050, "the <strong>hunting ban</strong>", false, "hunting");
		$got_dream |= display_dream_comparison($extra_info, $member, 826, "equal <strong>gay rights</strong>", false, "gay");
        $got_dream |= display_dream_comparison($extra_info, $member, 1030, "laws to <strong>stop climate change</strong>", false, "climate change");

		if (!$got_dream) {
			print "<li>" . $member['full_name'] . " has not voted enough in this parliament to have any scores.</li>";
		}
		print '</ul>';
?>
<p class="italic">
<small>Read about <a href="<?=WEBPATH ?>help/#votingrecord">how the voting record is decided</a>.</small>
</p>

<? } ?>

<?
		// Links to full record at Guardian and Public Whip	
		$record = array();
		if (isset($extra_info['guardian_howtheyvoted'])) {
			$record[] = '<a href="' . $extra_info['guardian_howtheyvoted'] . '" title="At The Guardian">well-known issues</a> <small>(from the Guardian)</small>';
		}
		if (isset($extra_info['public_whip_division_attendance']) && $extra_info['public_whip_division_attendance'] != 'n/a') { 
			$record[] = '<a href="http://www.publicwhip.org.uk/mp.php?id=uk.org.publicwhip/member/' . $member['member_id'] . '&amp;showall=yes#divisions" title="At Public Whip">their full record</a>';
		}

		if (count($record) > 0) {
			$displayed_stuff = 1;
			?>
			<p>More on <?php echo implode(' &amp; ', $record); ?></p>
<?php
		}
	        
		// Rebellion rate
		if (isset($extra_info['public_whip_rebellions']) && $extra_info['public_whip_rebellions'] != 'n/a') {	
			$displayed_stuff = 1;
	?>					<ul>
							<li><a href="http://www.publicwhip.org.uk/mp.php?id=uk.org.publicwhip/member/<?=$member['member_id'] ?>#divisions" title="See more details at Public Whip">
                        <strong><?php echo htmlentities(ucfirst($extra_info['public_whip_rebel_description'])); ?> rebels</strong></a> against their party<?php
			if (isset($extra_info['public_whip_rebelrank'])) {
				echo " in this parliament"; /* &#8212; ";
				if (isset($extra_info['public_whip_rebelrank_joint']))
					print 'joint ';
				echo make_ranking($extra_info['public_whip_rebelrank']);
				echo " most rebellious of ";
				echo $extra_info['public_whip_rebelrank_outof'];
				echo ($member['house']=='House of Commons') ? " MPs" : ' Lords';
				*/
			}
			?>.
			</li>
		</ul><?php
		}

		if (!$displayed_stuff) {
			print '<p>No data to display yet.</p>';
		}


		# Topics of interest only for MPs at the moment
		if ($member['current_member'][1]) { # in_array(1, $member['houses'])

?>	<a name="topics"></a>
    <h4>Committees and topics of interest</h4>
		<?
		$topics_block_empty = true;

		// Select committee membership
		if (array_key_exists('office', $extra_info)) {
			$mins = array();
			foreach ($extra_info['office'] as $row) {
				if ($row['to_date'] == '9999-12-31' && $row['source'] == 'chgpages/selctee') {
					$m = prettify_office($row['position'], $row['dept']);
					if ($row['from_date']!='2004-05-28')
						$m .= ' <small>(since ' . format_date($row['from_date'], SHORTDATEFORMAT) . ')</small>';
					$mins[] = $m;
					if ($row['dept'] == "Chairmen's Panel Committee")
						$chairmens_panel = true;
				}
			}
			if ($mins) {
				print "<h5>Select Committee membership</h5>";
				print "<ul>";
				foreach ($mins as $min) {
					print '<li>' . $min . '</li>';
				}
				print "</ul>";
				$topics_block_empty = false;
			}
		}
		$wrans_dept = false;
		$wrans_dept_1 = null;
		$wrans_dept_2 = null;
		if (isset($extra_info['wrans_departments'])) { 
				$wrans_dept = true;
				$wrans_dept_1 = "<li><strong>Departments:</strong> ".$extra_info['wrans_departments']."</p>";
		} 
		if (isset($extra_info['wrans_subjects'])) { 
				$wrans_dept = true;
				$wrans_dept_2 = "<li><strong>Subjects (based on headings added by Hansard):</strong> ".$extra_info['wrans_subjects']."</p>";
		} 
		
		if ($wrans_dept) {
			print "<p><strong>Asks most questions about</strong></p>";
			print "<ul>";
			if ($wrans_dept_1) print $wrans_dept_1;
			if ($wrans_dept_2) print $wrans_dept_2;
			print "</ul>";
			$topics_block_empty = false;
			$WRANSURL = new URL('search');
			$WRANSURL->insert(array('pid'=>$member['person_id'], 's'=>'section:wrans', 'pop'=>1));
		?>							<p><small>(based on <a href="<?=$WRANSURL->generate()?>">written questions asked by <?=$member['full_name']?></a> and answered by departments)</small></p><?
		}

		# Public Bill Committees
		if (count($extra_info['pbc'])) {
			$topics_block_empty = false;
			print '<h5>Public Bill Committees <small>(sittings attended)</small></h5>';
			if ($member['party'] == 'Scottish National Party') {
				echo '<p><em>SNP MPs only attend sittings where the legislation pertains to Scotland.</em></p>';
			}
			echo '<ul>';
			foreach ($extra_info['pbc'] as $bill_id => $arr) {
				print '<li>';
				if ($arr['chairman']) print 'Chairman, ';
				print '<a href="/pbc/' . $arr['session'] . '/' . urlencode($arr['title']) . '">'
					. $arr['title'] . ' Committee</a> <small>(' . $arr['attending']
					. ' out of ' . $arr['outof'] . ')</small>';
			}
			print '</ul>';
		}
		
		if ($topics_block_empty) {
			print "<p><em>This MP is not currently on any select or public bill committee
and has had no written questions answered for which we know the department or subject.</em></p>";
		}

		}
	}

	if (!in_array(1, $member['houses']) || $member['party'] != 'Sinn Fein' || in_array(3, $member['houses'])) {


	?>		<a name="hansard"></a> <?
		$title = 'Most recent appearances';
		if ($rssurl = $DATA->page_metadata($this_page, 'rss')) {
			$title = '<a href="' . WEBPATH . $rssurl . '"><img src="' . WEBPATH . 'images/rss.gif" alt="RSS feed" border="0" align="right"></a> ' . $title;
		}
        
        print "<h4>{$title}</h4>";
		//$this->block_start(array('id'=>'hansard', 'title'=>$title));
		// This is really far from ideal - I don't really want $PAGE to know
		// anything about HANSARDLIST / DEBATELIST / WRANSLIST.
		// But doing this any other way is going to be a lot more work for little 
		// benefit unfortunately.
	
	        twfy_debug_timestamp();
		$HANSARDLIST = new HANSARDLIST();
		
		$searchstring = "speaker:$member[person_id]";
		global $SEARCHENGINE;
		$SEARCHENGINE = new SEARCHENGINE($searchstring); 
		$args = array (
			's' => $searchstring,
			'p' => 1,
			'num' => 3,
		       'pop' => 1,
			'o' => 'd',
		);
		$HANSARDLIST->display('search_min', $args);
	        twfy_debug_timestamp();

		$MOREURL = new URL('search');
		$MOREURL->insert( array('pid'=>$member['person_id'], 'pop'=>1) );
		?>
	<p id="moreappear"><a href="<?php echo $MOREURL->generate(); ?>#n4">More of <?php echo ucfirst($member['full_name']); ?>'s recent appearances</a></p>

<?php
		if ($rssurl = $DATA->page_metadata($this_page, 'rss')) {
			// If we set an RSS feed for this page.
			$HELPURL = new URL('help');
			?>
					<p class="unneededprintlinks"><a href="<?php echo WEBPATH . $rssurl; ?>" title="XML version of this person's recent appearances">RSS feed</a> (<a href="<?php echo $HELPURL->generate(); ?>#rss" title="An explanation of what RSS feeds are for">?</a>)</p>
<?php
		}
		
	//	$this->block_end();

} # End Sinn Fein

		?> <a name="numbers"></a> <?php
		//$this->block_start(array('id'=>'numbers', 'title'=>'Numerology'));
		print "<h4>Numerology</h4>";
		$displayed_stuff = 0;
		?>
		<p><em>Please note that numbers do not measure quality. 
		Also, representatives may do other things not currently covered
		by this site.</em> (<a href="<?=WEBPATH ?>help/#numbers">More about this</a>)</p>
<ul>
<?php

		$since_text = 'in the last year';
		#if ($member['entered_house'] > '2005-05-05')
		#	$since_text = 'since joining Parliament';

		$MOREURL = new URL('search');
		$section = 'section:debates section:whall section:lords section:ni';
		$MOREURL->insert(array('pid'=>$member['person_id'], 's'=>$section, 'pop'=>1));
		if ($member['party']!='Sinn Fein') {
			$displayed_stuff |= display_stats_line('debate_sectionsspoken_inlastyear', 'Has spoken in <a href="' . $MOREURL->generate() . '">', 'debate', '</a> ' . $since_text, '', $extra_info);

		$MOREURL->insert(array('pid'=>$member['person_id'], 's'=>'section:wrans', 'pop'=>1));
		// We assume that if they've answered a question, they're a minister
		$minister = 0; $Lminister = false;
		if (isset($extra_info['wrans_answered_inlastyear']) && $extra_info['wrans_answered_inlastyear'] > 0 && $extra_info['wrans_asked_inlastyear'] == 0)
			$minister = 1;
		if (isset($extra_info['Lwrans_answered_inlastyear']) && $extra_info['Lwrans_answered_inlastyear'] > 0 && $extra_info['Lwrans_asked_inlastyear'] == 0)
			$Lminister = true;
		if ($member['party']=='Speaker' || $member['party']=='Deputy Speaker') {
			$minister = 2;
		}
		$displayed_stuff |= display_stats_line('wrans_asked_inlastyear', 'Has received answers to <a href="' . $MOREURL->generate() . '">', 'written question', '</a> ' . $since_text, '', $extra_info, $minister, $Lminister);
		}

		if (isset($extra_info['select_committees'])) {
			print "<li>Is a member of <strong>$extra_info[select_committees]</strong> select committee";
			if ($extra_info['select_committees'] != 1)
				print "s";
			if (isset($extra_info['select_committees_chair']))
				print " ($extra_info[select_committees_chair] as chair)";
			print '.</li>';
		}

		$wtt_displayed = display_writetothem_numbers(2008, $extra_info);
		$displayed_stuff |= $wtt_displayed;
		if (!$wtt_displayed) {
            $wtt_displayed = display_writetothem_numbers(2007, $extra_info);
            $displayed_stuff |= $wtt_displayed;
            if (!$wtt_displayed) {
                $wtt_displayed = display_writetothem_numbers(2006, $extra_info);
                $displayed_stuff |= $wtt_displayed;
                if (!$wtt_displayed)
                    $displayed_stuff |= display_writetothem_numbers(2005, $extra_info);
            }
        }

		$after_stuff = ' <small>(From Public Whip)</small>';
		if ($member['party'] == 'Scottish National Party') {
			$after_stuff .= '<br><em>Note SNP MPs do not vote on legislation not affecting Scotland.</em>';
		} elseif ($member['party']=='Speaker' || $member['party']=='Deputy Speaker') {
			$after_stuff .= '<br><em>Speakers and deputy speakers cannot vote except to break a tie.</em>';
		}
		if ($member['party'] != 'Sinn Fein') {
			$displayed_stuff |= display_stats_line('public_whip_division_attendance', 'Has voted in <a href="http://www.publicwhip.org.uk/mp.php?id=uk.org.publicwhip/member/' . $member['member_id'] . '&amp;showall=yes#divisions" title="See more details at Public Whip">', 'of vote', '</a> in parliament with this affiliation', $after_stuff, $extra_info);
			if ($chairmens_panel) {
				print '<br><em>Members of the Chairmen\'s Panel act for the Speaker when chairing things such as Public Bill Committees, and as such do not vote on Bills they are involved in chairing.</em>';
			}

			$displayed_stuff |= display_stats_line('comments_on_speeches', 'People have made <a href="' . WEBPATH . 'comments/recent/?pid='.$member['person_id'].'">', 'annotation', "</a> on this MP&rsquo;s speeches", '', $extra_info);
			$displayed_stuff |= display_stats_line('reading_age', 'This MP\'s speeches, in Hansard, are readable by an average ', '', ' year old, going by the <a href="http://en.wikipedia.org/wiki/Flesch-Kincaid_Readability_Test">Flesch-Kincaid Grade Level</a> score', '', $extra_info);
		}
		
		if (isset($extra_info['number_of_alerts'])) {
			$displayed_stuff = 1;
			?>
		<li><strong><?=htmlentities($extra_info['number_of_alerts']) ?></strong> <?=($extra_info['number_of_alerts']==1?'person is':'people are') ?> tracking whenever <?
if ($member['house_disp']==1) print 'this MP';
elseif ($member['house_disp']==2) print 'this peer';
elseif ($member['house_disp']==3) print 'this MLA';
elseif ($member['house_disp']==4) print 'this MSP';
elseif ($member['house_disp']==0) print $member['full_name']; ?> speaks<?php
			if ($member['current_member'][0] || $member['current_member'][2] || $member['current_member'][3] || ($member['current_member'][1] && $member['party'] != 'Sinn Fein') || $member['current_member'][4]) {
				print ' &mdash; <a href="' . WEBPATH . 'alert/?only=1&amp;pid='.$member['person_id'].'">email me whenever '. $member['full_name']. ' speaks</a>';
			}
			print '.</li>';
		}

		if ($member['party']!='Sinn Fein') {
			$displayed_stuff |= display_stats_line('three_word_alliterations', 'Has used three-word alliterative phrases (e.g. "she sells seashells") ', 'time', ' in debates', ' <small>(<a href="' . WEBPATH . 'help/#numbers">Why is this here?</a>)</small>', $extra_info);
			if (isset($extra_info['three_word_alliteration_content'])) {
					print "\n<!-- " . $extra_info['three_word_alliteration_content'] . " -->\n";
			}

		}
		#		$displayed_stuff |= display_stats_line('ending_with_a_preposition', "Has ended a sentence with 'with' ", 'time', ' in debates', '', $extra_info);
		#		$displayed_stuff |= display_stats_line('only_asked_why', "Has made a speech consisting solely of 'Why?' ", 'time', ' in debates', '', $extra_info);


?>
						</ul>
<?php
		if (!$displayed_stuff) {
			print '<p>No data to display yet.</p>';
		}
		//$this->block_end();

		if (isset($extra_info['register_member_interests_html'])) {
?>				
<a name="register"></a>
<?php
			print "<h4>Register of Members' Interests</h4>";

			if ($extra_info['register_member_interests_html'] != '') {
				echo $extra_info['register_member_interests_html'];
			} else {
				echo "\t\t\t\t<p>Nil</p>\n";
			}
			echo '<p class="italic">';
			if (isset($extra_info['register_member_interests_date'])) {
				echo 'Register last updated: ';
				echo format_date($extra_info['register_member_interests_date'], SHORTDATEFORMAT);
				echo '. ';
			}
			echo '<a href="http://www.publications.parliament.uk/pa/cm/cmregmem/061106/memi01.htm">More about the Register</a>';
			echo '</p>';
			print '<p><strong><a href="' . WEBPATH . 'regmem/?p='.$member['person_id'].'">View the history of this MP\'s entries in the Register</a></strong></p>';
		}

		if (isset($extra_info['expenses2004_col1']) || isset($extra_info['expenses2006_col1']) || 
isset($extra_info['expenses2007_col1']) || isset($extra_info['expenses2008_col1'])) {
			include_once INCLUDESPATH . 'easyparliament/expenses.php';
?>
<a name="expenses"></a>
<?php
			$title = 'Expenses';
			print "<h4>" . $title . "</h4>";
			echo expenses_display_table($extra_info);
		}

	}
	
	function generate_member_links ($member, $links) {
		// Receives its data from $MEMBER->display_links;
		// This returns HTML, rather than outputting it.
		// Why? Because we need this to be in the sidebar, and 
		// we can't call the MEMBER object from the sidebar includes
		// to get the links. So we call this function from the mp
		// page and pass the HTML through to stripe_end(). Better than nothing.

		// Bah, can't use $this->block_start() for this, as we're returning HTML...
		$html = '<div class="block">
				<h4>More useful links for this person</h4>
				<div class="blockbody">
				<ul' . (get_http_var('c4')?' style="list-style-type:none;"':''). '>';

		if (isset($links['maiden_speech'])) {
			$maiden_speech = fix_gid_from_db($links['maiden_speech']);
			$html .= '<li><a href="' . WEBPATH . 'debate/?id=' . $maiden_speech . '">Maiden speech</a></li>';
		}

		// BIOGRAPHY.
		global $THEUSER;
		if (isset($links['mp_website'])) {
			$html .= '<li><a href="' . $links['mp_website'] . '">'. $member->full_name().'\'s personal website</a>';
			if ($THEUSER->is_able_to('viewadminsection')) {
				$html .= ' [<a href="/admin/websites.php?editperson=' .$member->person_id() . '">Edit</a>]';
			}
			$html .= '</li>';
		} elseif ($THEUSER->is_able_to('viewadminsection')) {
			 $html .= '<li>[<a href="/admin/websites.php?editperson=' . $member->person_id() . '">Add personal website</a>]</li>';
		}

		if (isset($links['sp_url'])) {
			$html .= '<li><a href="' . $links['sp_url'] . '">'. $member->full_name().'\'s page on the Scottish Parliament website</a></li>';
		}

		if(isset($links['guardian_biography'])) {
			$html .= '	<li><a href="' . $links['guardian_biography'] . '">Biography</a> <small>(From The Guardian)</small></li>';
		}
		if(isset($links['wikipedia_url'])) {
			$html .= '	<li><a href="' . $links['wikipedia_url'] . '">Biography</a> <small>(From Wikipedia)</small></li>';
		}
		
		if(isset($links['diocese_url'])) {
			$html .= '	<li><a href="' . $links['diocese_url'] . '">Diocese website</a></li>';
		}

		if (isset($links['journa_list_link'])) {
			$html .= '	<li><a href="' . $links['journa_list_link'] . '">Newspaper articles written by this MP</a> <small>(From Journalisted)</small></li>';

		} 
		
		if (isset($links['guardian_parliament_history'])) {
			$html .= '	<li><a href="' . $links['guardian_parliament_history'] . '">Parliamentary career</a> <small>(From The Guardian)</small></li>';
		}

		if (isset($links['guardian_election_results'])) {
			$html .= '	<li><a href="' . $links['guardian_election_results'] . '">Election results for ' . $member->constituency() . '</a> <small>(From The Guardian)</small></li>';
		}

		if (isset($links['guardian_candidacies'])) {
			$html .= '	<li><a href="' . $links['guardian_candidacies'] . '">Previous candidacies</a> <small>(From The Guardian)</small></li>';
		}

		if (isset($links['guardian_contactdetails'])) {
			$html .= '	<li><a href="' . $links['guardian_contactdetails'] . '">Contact details</a> <small>(From The Guardian)</small></li>';
		}

		if (isset($links['bbc_profile_url'])) {
			$html .= '	<li><a href="' . $links['bbc_profile_url'] . '">General information</a> <small>(From BBC News)</small></li>';

		} 
		/*
		# BBC Catalogue is offline
		$bbc_name = urlencode($member->first_name()) . "%20" . urlencode($member->last_name());
		if ($member->member_id() == -1)
			$bbc_name = 'Queen Elizabeth';
		$html .= '	<li><a href="http://catalogue.bbc.co.uk/catalogue/infax/search/' . $bbc_name . '">TV/radio appearances</a> <small>(From BBC Programme Catalogue)</small></li>';
		*/

		
		$html .= "	</ul>
					</div>
				</div> <!-- end block -->
";
		return $html;
	}
	
	
	function error_message ($message, $fatal = false, $status = 500) {
		// If $fatal is true, we exit the page right here.
		// $message is like the array used in $this->message()
			
		if (!$this->page_started()) {
			header("HTTP/1.0 $status Internal Server Error");
			$this->page_start();
		}
		
		if (is_string($message)) {
			// Sometimes we're just sending a single line to this function
			// rather like the bigger array...
			$message = array (
				'text' => $message
			);
		}
		
		$this->message($message, 'error');
			
		if ($fatal) {
			if ($this->within_stripe()) {
				$this->stripe_end();
			}
			$this->page_end();
		}
	
	}
	
	
	function message ($message, $class='') {
		// Generates a very simple but common page content.
		// Used for when a user logs out, or votes, or any simple thing
		// where there's a little message and probably a link elsewhere.
		// $message is an array like:
		// 		'title' => 'You are now logged out'.
		//		'text'	=> 'Some more text here',
		//		'linkurl' => 'http://www.easyparliament.org/debates/',
		//		'linktext' => 'Back to previous page'
		// All fields optional.
		// 'linkurl' should already have htmlentities done on it.
		// $class is a class name that will be applied to the message's HTML elements.
		
		if ($class != '') {
			$class = ' class="' . $class . '"';
		}
		
		$need_to_close_stripe = false;
		
		if (!$this->within_stripe()) {
			$this->stripe_start();
			$need_to_close_stripe = true;
		}

		if (isset($message['title'])) {
			?>
			<h3<?php echo $class; ?>><?php echo $message['title']; ?></h3>
<?php
		}
		
		if (isset($message['text'])) {
			?>
			<p<?php echo $class; ?>><?php echo $message['text']; ?></p>
<?php
		}
		
		if (isset($message['linkurl']) && isset($message['linktext'])) {
			?>
			<p><a href="<?php echo $message['linkurl']; ?>"><?php echo $message['linktext']; ?></a></p>
<?php
		}
		
		if ($need_to_close_stripe) {
			$this->stripe_end();
		}
	}
	
	

	function set_hansard_headings ($info) {
		// Called from HANSARDLIST->display().
		// $info is the $data['info'] array passed to the template.
		// If the page's HTML hasn't already been started, it sets the page
		// headings that will be needed later in the page.
		
		global $DATA, $this_page;

		if (!$this->page_started()) {
			// The page's HTML hasn't been started yet, so we'd better do it.

			// Set the page title (in the <title></title>).

			$page_title = '';

			if (isset($info['text'])) {
				// Use a truncated version of the page's main item's body text.
				// trim_words() is in utility.php. Trim to 40 chars.
				$page_title = trim_characters($info['text'], 0, 40);
				
			} elseif (isset($info['year'])) {
				// debatesyear and wransyear pages.
				$page_title = $DATA->page_metadata($this_page, 'title');

				$page_title .= $info['year'];	
			}

			if (isset($info['date'])) {
				// debatesday and wransday pages.
				if ($page_title != '') {
					$page_title .= ': ';
				}
				$page_title .= format_date ($info['date'], SHORTDATEFORMAT);
			}
			
			if ($page_title != '') {
				$DATA->set_page_metadata($this_page, 'title', $page_title);
			}
		
			if (isset($info['date'])) {
				// Set the page heading (displayed on the page).
				$page_heading = format_date($info['date'], LONGERDATEFORMAT);
				$DATA->set_page_metadata($this_page, 'heading', $page_heading);
			}
	
		}

	}

	
	function nextprevlinks () {
		
		// Generally called from $this->stripe_end();
		
		global $DATA, $this_page;

		// We'll put the html in these and print them out at the end of the function...
		$prevlink = '';
		$uplink = '';
		$nextlink = '';
	
		// This data is put in the metadata in hansardlist.php
		$nextprev = $DATA->page_metadata($this_page, 'nextprev');
		// $nextprev will have three arrays: 'prev', 'up' and 'next'.
		// Each should have a 'body', 'title' and 'url' element.


		// PREVIOUS ////////////////////////////////////////////////

		if (isset($nextprev['prev'])) {

			$prev = $nextprev['prev'];

			if (isset($prev['url'])) {	
				$prevlink = '<a href="' . $prev['url'] . '" title="' . $prev['title'] . '" class="linkbutton">&laquo; ' . $prev['body'] . '</a>';
		
			} else {
				$prevlink = '&laquo; ' . $prev['body'];
			}
		}
		
		if ($prevlink != '') {
			$prevlink = '<span class="prev">' . $prevlink . '</span>';
		}
		
		
		// UP ////////////////////////////////////////////////
		
		if (isset($nextprev['up'])) {

			$uplink = '<span class="up"><a href="' .  $nextprev['up']['url'] . '" title="' . $nextprev['up']['title'] . '">' . $nextprev['up']['body'] . '</a></span>';
		}
		
		
		// NEXT ////////////////////////////////////////////////

		if (isset($nextprev['next'])) {
			$next = $nextprev['next'];
			
			if (isset($next['url'])) {
				$nextlink = '<a href="' .  $next['url'] . '" title="' . $next['title'] . '" class="linkbutton">' . $next['body'] . ' &raquo;</a>';
			} else {
				$nextlink = $next['body'] . ' &raquo;';
			}
		}
		
		if ($nextlink != '') {
			$nextlink = '<span class="next">' . $nextlink . '</span>';
		}
		
		
		if ($uplink || $prevlink || $nextlink) {
			echo '<p class="nextprev">', $uplink, ' ', $nextlink, ' ', $prevlink, '</p><br class="clear"/>';
		}
	}

	 
	function recess_message() {
		// Returns a message if parliament is currently in recess.
		include_once INCLUDESPATH."easyparliament/recess.php";
		$message = '';
		list($name, $from, $to) = recess_prettify(date('j'), date('n'), date('Y'), 1);
		if ($name) {
			$message = 'The Houses of Parliament are in their ' . $name . ' ';
			if ($from && $to) {
				$from = format_date($from, SHORTDATEFORMAT);
				$to = format_date($to, SHORTDATEFORMAT);
				if (substr($from, -4, 4) == substr($to, -4, 4)) {
					$from = substr($from, 0, strlen($from) - 4);
				}
				$message .= "from $from until $to.";
			} else {
				$message .= 'at this time.';
			}
		}

		return $message;
	}

	function trackback_rss ($trackbackdata) {
		/*
		Outputs Trackback Auto Discovery RSS for something.
		
		$trackbackdata = array (
			'itemurl' 	=> 'http://www.easyparliament.org/debate/?id=2003-02-28.544.2',
			'pingurl' 	=> 'http://www.easyparliament.org/trackback/?e=2345',
			'title' 	=> 'This item or page title',
			'date' 		=> '2003-02-28T13:47:00+00:00'
		);
		*/
		?>
<!--
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:dc="http://purl.org/dc/elements/1.1/"
         xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">
<rdf:Description
    rdf:about="<?php echo $trackbackdata['itemurl']; ?>"
    trackback:ping="<?php echo $trackbackdata['pingurl']; ?>"
    dc:identifier="<?php echo $trackbackdata['itemurl']; ?>"
    dc:title="<?php echo str_replace('"', "'", $trackbackdata['title']); ?>"
    dc:date="<?php echo $trackbackdata['date']; ?>">
</rdf:RDF>
-->
<?php
	}

	function search_form ($value='') {
		global $SEARCHENGINE;
		// Search box on the search page.
		// If $value is set then it will be displayed in the form.
		// Otherwise the value of 's' in the URL will be displayed.

		$wtt = get_http_var('wtt');

		$URL = new URL('search');
		$URL->reset(); // no need to pass any query params as a form action. They are not used.
		
		if ($value == '')
			$value = get_http_var('s');

		$person_name = '';
		if (preg_match_all('#speaker:(\d+)#', $value, $m) == 1) {
			$person_id = $m[1][0];
			$member = new MEMBER(array('person_id' => $person_id));
			if ($member->valid) {
				$value = str_replace("speaker:$person_id", '', $value);
		        	$person_name = $member->full_name();
	    		}
       		}

		echo '<div class="mainsearchbox">';
		if ($wtt<2) {
    			echo '<form action="', $URL->generate(), '" method="get">';
    			if (get_http_var('o')) {
    				echo '<input type="hidden" name="o" value="', htmlentities(get_http_var('o')), '">';
    			}
    			if (get_http_var('house')) {
    				echo '<input type="hidden" name="house" value="', htmlentities(get_http_var('house')), '">';
    			}
    			echo '<input type="text" name="s" value="', htmlentities($value), '" size="50"> ';
    			echo '<input type="submit" value=" ', ($wtt?'Modify search':'Search'), ' ">';
    			$URL = new URL('search');
			$URL->insert(array('adv' => 1));
    			echo '&nbsp;&nbsp; <a href="' . $URL->generate() . '">More options</a>';
    			echo '<br>';
    			if ($wtt) print '<input type="hidden" name="wtt" value="1">';
		} else { ?>
	<form action="http://www.writetothem.com/lords" method="get">
	<input type="hidden" name="pid" value="<?=htmlentities(get_http_var('pid')) ?>">
	<input type="submit" style="font-size: 150%" value=" I want to write to this Lord "><br>
<?
		}

		if (!$wtt && ($value || $person_name)) {
			echo '<div style="margin-top: 5px">';
			$orderUrl = new URL('search');
			$orderUrl->insert(array('s'=>$value)); # Need the parsed value
		        $ordering = get_http_var('o');
		        if ($ordering!='r' && $ordering!='d' && $ordering != 'p') {
		            $ordering='d';
		        }
        
		        if ($ordering=='r') {
				print '<strong>Most relevant results are first</strong>';
		        } else {
				printf("<a href='%s'>Show most relevant results first</a>", $orderUrl->generate('html', array('o'=>'r')));
		        }

		        print "&nbsp;|&nbsp;";
		        if ($ordering=='d') {
				print '<strong>Most recent results are first</strong>';
		        } else {
				printf("<a href='%s'>Show most recent results first</a>", $orderUrl->generate('html', array('o'=>'d')));
		        }

			print "&nbsp;|&nbsp;";
			if ($ordering=='p') {
				print '<strong>Use by person</strong>';
			} else {
				printf('<a href="%s">Show use by person</a>', $orderUrl->generate('html', array('o'=>'p')));
			}
			echo '</div>';

			if ($person_name) {
                ?>
                    <p>
                    <input type="radio" name="pid" value="<?php echo htmlentities($person_id) ?>" checked>Search only <?php echo htmlentities($person_name) ?> 
                    <input type="radio" name="pid" value="">Search all speeches
                    </p>
                <?
       			}
		}

		echo '</form> </div>';
	}

	function advanced_search_form() { ?>
	    
<h2>Search</h2>

<form action="/search/" method="get" id="search-form">

<div id="term-search">
    <label for="s" class="hide">Search</label> <input type="text" id="s" name="s" value="<?=htmlspecialchars(get_http_var('s')) ?>">
    <div class="help">
    Enter what you&rsquo;re looking for here. See the help to the right for how
    to search for <strong>"exact phrases"</strong>, <strong>-exclude -words</strong>, or perform <strong>NEARby OR boolean</strong> searches.
    </div>
</div>

<h3>Filters</h3>

<div><label for="from">Date range</label>
From <input type="text" id="from" name="from" value="<?=htmlspecialchars(get_http_var('from')) ?>" size="22">
 to <input type="text" name="to" value="<?=htmlspecialchars(get_http_var('to')) ?>" size="22">
</div>
<div class="help">
You can give a <strong>start date, an end date, or both</strong>, to restrict results to a
particular date range; a missing end date implies the current date, a missing start date
implies the oldest date we have in the system. Dates can be entered in any format you wish, <strong>e.g.
&ldquo;3rd March 2007&rdquo; or &ldquo;17/10/1989&rdquo;</strong>.
</div>

<div><label for="person">Person</label>
<input type="text" id="person" name="person" value="<?=htmlspecialchars(get_http_var('person')) ?>" size="40">
</div>
<div class="help">
Enter a name here to restrict results to contributions only by that person.
</div>

<div><label for="department">Department</label> <select name="department" id="department">
<option value="">-
<option>Administration Committee
<option>Advocate-General
<option>Advocate-General for Scotland
<option>Agriculture, Fisheries and Food
<option>Attorney-General
<option>Business, Enterprise and Regulatory Reform
<option>Cabinet Office
<option>Children, Schools and Families
<option>Church Commissioners
<option>Civil Service
<option>Communities and Local Government
<option>Constitutional Affairs
<option>Culture Media and Sport
<option>Defence
<option>Deputy Prime Minister
<option>Duchy of Lancaster
<option>Education
<option>Education and Science
<option>Education and Skills
<option>Electoral Commission Committee
<option>Employment
<option>Energy
<option>Environment
<option>Environment Food and Rural Affairs
<option>European Community
<option>Foreign and Commonwealth Affairs
<option>Foreign and Commonwealth Office
<option>Government Equalities Office
<option>Health
<option>Home Department
<option>House of Commons
<option>House of Commons Commission
<option>House of Lords
<option>Industry
<option>Innovation, Universities and Skills
<option>International Development
<option>Justice
<option>Leader of the Council
<option>Leader of the House
<option>Lord Chancellor
<option>Minister for Women
<option>Minister for Women and Equality
<option>National Finance
<option>Northern Ireland
<option>Olympics
<option>Overseas Development
<option>Palace of Westminister
<option>President of the Council
<option>Prime Minister
<option>Privy Council
<option>Public Accounts Commission
<option>Public Accounts Committee
<option>Scotland
<option>Social Services
<option>Solicitor General
<option>Solicitor-General
<option>Trade
<option>Trade and Industry
<option>Transport
<option>Transport, Local Government and the Regions
<option>Treasury
<option>Wales
<option>Women and Equality
<option>Work and Pensions
</select>
</div>
<div class="help">
This will restrict results to those UK Parliament written answers and statements from the chosen department.
<small>The department list might be slightly out of date.</small>
</div>

<div><label for="party">Party</label> <select id="party" name="party">
<option value="">-
<option>Alliance
<option value="Bp">Bishops
<option value="CWM,DCWM">Commons Deputy Speakers
<option value="SPK">Commons Speaker
<option value="Con">Conservative
<option value="XB">Crossbench Lords
<option>DUP
<option>Green
<option value="Ind,Independent">Independent
<!--
All broken
<option value="Ind Con">Ind Con (Commons)
<option value="Ind Lan">Ind Lab (Commons)
<option value="Ind UU">Ind UU (Commons)
<option>Independent Unionist
<option>Initial Presiding Officer, Scottish Parliament
-->
<option value="Lab,Lab/Co-op">Labour
<option value="LDem">Liberal Democrat
<option value="Speaker">NI Speaker
<option>NIUP
<option>NIWC
<option value="Other">Other (Lords)
<option value="PC">Plaid Cymru
<option>PUP
<option value="Res">Respect
<option value="None">Scottish Parliament Speaker
<option>SDLP
<option>SG
<!-- 
Sinn Fein is broken
<option value="SF,Sinn F&eacute;in">Sinn F&eacute;in
-->
<option>SNP
<option>SSCUP
<option>SSP
<option>UKIP
<option>UKUP
<option>UUAP
<option>UUP
</select>
</div>
<div class="help">
Restricts results to the chosen party
<br><small>(there is currently a bug with some parties, such as Sinn F&eacute;in)</small>.
</div>

<div><label for="section">Section</label>
<select id="section" name="section">
<option value="">-
<optgroup label="UK Parliament">
<option value="uk">All
<option value="debates">House of Commons debates
<option value="whall">Westminster Hall debates
<option value="lords">House of Lords debates
<option value="wrans">Written answers
<option value="wms">Written ministerial statements
<option value="standing">Public Bill Committees
</optgroup>
<optgroup label="Northern Ireland Assembly">
<option value="ni">Debates
</optgroup>
<optgroup label="Scottish Parliament">
<option value="scotland">All
<option value="sp">Debates
<option value="spwrans">Written answers
</optgroup>
<!--
<optgroup label="Scottish Parliament">
<option value="scotland">All
<option value="sp">Debates
<option value="spwrans">Written answers
</optgroup>
-->
</select>
</div>
<div class="help">
Restrict results to a particular parliament or assembly that we cover (e.g. the
Scottish Parliament), or a particular type of data within an institution, such
as Commons Written Answers.
</div>

<div><label for="column">Column</label>
<input type="text" id="column" name="column" value="<?=htmlspecialchars(get_http_var('column')) ?>" size="10">
</div>
<div class="help">
If you know the actual column number in Hansard you are interested in (perhaps you&rsquo;re looking up a paper
reference), you can restrict results to that.
</div>

<p align="right">
<input type="submit" value="Search">
</p>
</form>
<?
	}
	
	function login_form ($errors = array()) {
		// Used for /user/login/ and /user/prompt/
		// $errors is a hash of potential errors from a previous log in attempt.
		?>
				<form method="post" action="<?php $URL = new URL('userlogin'); $URL->reset(); echo $URL->generate(); ?>">


<?php
		if (isset($errors["email"])) {
			$this->error_message($errors['email']);
		}
		if (isset($errors["invalidemail"])) {
			$this->error_message($errors['invalidemail']);
		}
?>
				<div class="row">
				<span class="label"><label for="email">Email address:</label></span>
				<span class="formw"><input type="text" name="email" id="email" value="<?php echo htmlentities(get_http_var("email")); ?>" maxlength="100" size="30" class="form"></span>
				</div>

<?php
		if (isset($errors["password"])) {
			$this->error_message($errors['password']);
		}
		if (isset($errors["invalidpassword"])) {
			$this->error_message($errors['invalidpassword']);
		}
?>
				<div class="row">
				<span class="label"><label for="password">Password:</label></span>
				<span class="formw"><input type="password" name="password" id="password" maxlength="30" size="20" class="form"></span>
				</div>

				<div class="row">
				<span class="label">&nbsp;</span>
				<span class="formw"><input type="checkbox" name="remember" id="remember" value="true"<?php
		$remember = get_http_var("remember");
		if (get_http_var("submitted") != "true" || $remember == "true") {
			print " checked";
		}
		?>> <label for="remember">Remember login details.*</label></span>
				</div>

				<div class="row">
				<span class="label">&nbsp;</span>
				<span class="formw"><input type="submit" value="Login" class="submit"> <small><a href="<?php 
		$URL = new URL("userpassword");
		$URL->insert(array("email"=>get_http_var("email")));
		echo $URL->generate(); 
?>">Forgotten your password?</a></small></span>
				</div>

				<div class="row">
				<small></small>
				</div>

				<input type="hidden" name="submitted" value="true">
<?php
		// I had to havk about with this a bit to cover glossary login.
		// Glossary returl can't be properly formatted until the "add" form
		// has been submitted, so we have to do this rubbish:
		global $glossary_returl;
		if ((get_http_var("ret") != "") || ($glossary_returl != "")) {
			// The return url for after the user has logged in.
			if (get_http_var("ret") != "") {
				$returl = get_http_var("ret");
			}
			else {
				$returl = $glossary_returl;
			}
			?>
				<input type="hidden" name="ret" value="<?php echo htmlentities($returl); ?>">
<?php
		}
		?>
				</form>
<?php
	}
	
	
	
	function mp_search_form ($person_id) {
		// Search box on the MP page.
	
		$URL = new URL('search');
		$URL->remove(array('s'));
		?>
				<div class="mpsearchbox">
					<form action="<?php echo $URL->generate(); ?>" method="get">
                    <p>
                    <input name="s" size="12"> 
                    <input type="hidden" name="pid" value="<?=$person_id ?>">
                    <input type="submit" class="submit" value="GO"></p>
					</form>
				</div>
<?php
	}

	
	function glossary_search_form ($args) {
		// Search box on the glossary page.
		global $THEUSER;
		
		$type = "";

		if (isset($args['blankform']) && $args['blankform'] == 1) {
			$formcontent = "";
		}
		else {
			$formcontent = htmlentities(get_http_var('g'));
		}
		
		if ($THEUSER->isloggedin()) {
			$URL = new URL($args['action']);
			$URL->remove(array('g'));
		}
		else {
			$URL = new URL('userprompt');
			$URL->remove(array('g'));
			$type = "<input type=\"hidden\" name=\"type\" value=\"2\">";
		}
		
		$add_link = $URL->generate('url');
		?>
		<form action="<?php echo $add_link; ?>" method="get">
		<?php echo $type; ?>
		<p>Help make TheyWorkForYou.com better by adding a definition:<br>
		<label for="g"><input type="text" name="g" value="<?php echo $formcontent; ?>" size="45">
		<input type="submit" value="Search" class="submit"></label>
		</p>
		</form>
<?php
	}

	function glossary_add_definition_form ($args) {
		// Add a definition for a new Glossary term.
		global $GLOSSARY;
		
		$URL = new URL($args['action']);
		$URL->remove(array('g'));
		
		?>
	<div class="glossaryaddbox">
		<form action="<?php print $URL->generate(); ?>" method="post">
		<input type="hidden" name="g" value="<?php echo $args['s']; ?>">
		<input type="hidden" name="return_page" value="glossary">
		<label for="definition"><p><textarea name="definition" id="definition" rows="15" cols="55"><?php echo htmlentities($GLOSSARY->current_term['body']); ?></textarea></p>
		
		<p><input type="submit" name="previewterm" value="Preview" class="submit">
		<input type="submit" name="submitterm" value="Post" class="submit"></p></label>
		<p><small>Only &lt;b&gt; and &lt;i&gt; tags are allowed. URLs and email addresses will automatically be turned into links.</small></p>
	</div>
<?php
	}

	function glossary_add_link_form ($args) {
		// Add an external link to the glossary.
		global $GLOSSARY;
		
		$URL = new URL('glossary_addlink');
		$URL->remove(array('g'));
		?>
	<h4>All checks fine and dandy!</h4><p>Just so you know, we found <strong><?php echo $args['count']; ?></strong> occurences of <?php echo $GLOSSARY->query; ?> in Hansard</p>
	<p>Please add your link below:</p>
	<h4>Add an external link for <em><?php echo $args['s']; ?></em></h4>
	<div class="glossaryaddbox">
		<form action="<?php print $URL->generate(); ?>" method="post">
		<input type="hidden" name="g" value="<?php echo $args['s']; ?>">
		<input type="hidden" name="return_page" value="glossary">
		<label for="definition"><input type="text" name="definition" id="definition">
		<p><!-- input type="submit" name="previewterm" value="Preview" class="submit" /-->
		<input type="submit" name="submitterm" value="Post" class="submit"></p></label>
		<p><small>Only &lt;b&gt; and &lt;i&gt; tags are allowed. URLs and email addresses will automatically be turned into links.</small></p>
	</div>
<?php
	}
	
	function glossary_atoz(&$GLOSSARY) {
	// Print out a nice list of lettered links to glossary pages	
				
		$letters = array ();
		
		foreach ($GLOSSARY->alphabet as $letter => $eps) {
			// if we're writing out the current letter (list or item)
			if ($letter == $GLOSSARY->current_letter) {
				// if we're in item view - show the letter as "on" but make it a link
				if ($GLOSSARY->current_term != '') {
					$URL = new URL('glossary');
					$URL->insert(array('az' => $letter));
					$letter_link = $URL->generate('url');
					
					$letters[] = "<li class=\"on\"><a href=\"" . $letter_link . "\">" . $letter . "</a></li>";
				}
				// otherwise in list view show no link
				else {
					$letters[] = "<li class=\"on\">" . $letter . "</li>";
				}
			}
			elseif (!empty($GLOSSARY->alphabet[$letter])) {
				$URL = new URL('glossary');
				$URL->insert(array('az' => $letter));
				$letter_link = $URL->generate('url');
				
				$letters[] = "<li><a href=\"" . $letter_link . "\">" . $letter . "</a></li>";
			}
			else {
				$letters[] = '<li>' . $letter . '</li>';
			}
		}
		?>
					<div class="letters">
						<ul>
	<?php
		for($n=0; $n<13; $n++) {
			print $letters[$n];
		}
		?>
						</ul>
						<ul>
	<?php
		for($n=13; $n<26; $n++) {
			print $letters[$n];
		}
		?>
						</ul>
					</div>
		<?php
	}

	function glossary_display_term(&$GLOSSARY) {
	// Display a single glossary term
		global $this_page;
		
		$term = $GLOSSARY->current_term;

		$term['body'] = $GLOSSARY->glossarise($term['body'], 0, 1);

		// add some extra controls for the administrators
		if ($this_page == "admin_glossary"){
			print "<a id=\"gl".$term['glossary_id']."\"></a>";
			print "<h3>" . $term['title'] . "</h3>";
			$URL = new URL('admin_glossary');
			$URL->insert(array("delete_confirm" => $term['glossary_id']));
			$delete_url = $URL->generate();
			$admin_links = "<br><small><a href=\"".$delete_url."\">delete</a></small>";
		}
		else {
			$admin_links = "";
		}

		if (isset($term['user_id'])) {
			$URL = new URL('userview');
			$URL->insert(array('u' => $term['user_id']));
			$user_link = $URL->generate('url');
			
			$user_details = "\t\t\t\t<p><small>contributed by user <a href=\"" . $user_link . "\">" . $term['firstname'] . " " . $term['lastname'] . "</a></small>" . $admin_links . "</p>\n";
		}
		else {
			$user_details = "";
		}

		print "\t\t\t\t<p class=\"glossary-body\">" . $term['body'] . "</p>\n" . $user_details;

		if ($this_page == "glossary_item") {
			// Add a direct search link for current glossary item
			$URL = new URL('search');
			// remember to quote the term for phrase matching in search
			$URL->insert(array('s' => '"'.$term['title'].'"'));
			$search_url = $URL->generate();
			printf ("\t\t\t\t<p>Search hansard for \"<a href=\"%s\" title=\"View search results for this glossary item\">%s</a>\"</p>", $search_url, $term['title']);
		}
	}



	function glossary_display_match_list(&$GLOSSARY) {
			if ($GLOSSARY->num_search_matches > 1) {
				$plural = "them";
				$definition = "some definitions";
			} else {
				$plural = "it";
				$definition = "a definition";
			}
			?>
			<h4>Found <?php echo $GLOSSARY->num_search_matches; ?> matches for <em><?php echo $GLOSSARY->query; ?></em></h4>
			<p>It seems we already have <?php echo $definition; ?> for that. Would you care to see <?php echo $plural; ?>?</p>
			<ul class="glossary"><?
			foreach ($GLOSSARY->search_matches as $match) {
				$URL = new URL('glossary');
				$URL->insert(array('gl' => $match['glossary_id']));
				$URL->remove(array('g'));
				$term_link = $URL->generate('url');
				?><li><a href="<?php echo $term_link ?>"><?php echo $match['title']?></a></li><?
			}
			?></ul>
<?php
	}

	function glossary_addterm_link() {
		// print a link to the "add glossary term" page
		$URL = new URL('glossary_addterm');
		$URL->remove(array("g"));
		$glossary_addterm_link = $URL->generate('url');
		print "<small><a href=\"" . $glossary_addterm_link . "\">Add a term to the glossary</a></small>";
	}

	function glossary_addlink_link() {
		// print a link to the "add external link" page
		$URL = new URL('glossary_addlink');
		$URL->remove(array("g"));
		$glossary_addlink_link = $URL->generate('url');
		print "<small><a href=\"" . $glossary_addlink_link . "\">Add an external link</a></small>";
	}


	function glossary_link() {
		// link to the glossary with no epobject_id - i.e. show all entries
		$URL = new URL('glossary');
		$URL->remove(array("g"));
		$glossary_link = $URL->generate('url');
		print "<small><a href=\"" . $glossary_link . "\">Browse the glossary</a></small>";
	}
	
	function glossary_links() {
		print "<div>";
		$this->glossary_link();
		print "<br>";
		$this->glossary_addterm_link();
		print "</div>";
	}
	
	function page_links ($pagedata) {
		// The next/prev and page links for the search page.
		global $this_page;
		
		// $pagedata has...
		$total_results 		= $pagedata['total_results'];
		$results_per_page 	= $pagedata['results_per_page'];
		$page 				= $pagedata['page'];
		
		
		if ($total_results > $results_per_page) {
			
			$numpages = ceil($total_results / $results_per_page);
			
			$pagelinks = array();
			
			// How many links are we going to display on the page - don't want to 
			// display all of them if we have 100s...
			if ($page < 10) {
				$firstpage = 1;
				$lastpage = 10;
			} else {
				$firstpage = $page - 10;
				$lastpage = $page + 9;
			}
		
			if ($firstpage < 1) {
				$firstpage = 1;
			}	
			if ($lastpage > $numpages) {
				$lastpage = $numpages;
			}
		
			// Generate all the page links.
			$URL = new URL($this_page);
			$URL->insert( array('wtt' => get_http_var('wtt')) );
			if (isset($pagedata['s'])) {
				# XXX: Should be taken out in *one* place, not here + search_form etc.
				$value = $pagedata['s'];
				if (preg_match_all('#speaker:(\d+)#', $value, $m) == 1) {
					$person_id = $m[1][0];
					$value = str_replace('speaker:' . $person_id, '', $value);
					$URL->insert(array('pid' => $person_id));
		       		}
				$URL->insert(array('s' => $value));
			}

			for ($n = $firstpage; $n <= $lastpage; $n++) {
				
				if ($n > 1) {
					$URL->insert(array('p'=>$n));
				} else {
					// No page number for the first page.
					$URL->remove(array('p'));
				}
				if (isset($pagedata['pid'])) {
					$URL->insert(array('pid'=>$pagedata['pid']));
				}
				
				if ($n != $page) {
					$pagelinks[] = '<a href="' . $URL->generate() . '">' . $n . '</a>';
				} else {
					$pagelinks[] = "<strong>$n</strong>";
				}
			}
			
			// Display everything.
			
			?>
				<div class="pagelinks">
					Result page: 
<?php
			
			if ($page != 1) {
				$prevpage = $page - 1;
				$URL->insert(array('p'=>$prevpage));
				?>
					<big><strong><a href="<?php echo $URL->generate(); ?>"><big>&laquo;</big> Previous</a></strong></big>
<?php
			}
			
			echo "\t\t\t\t" . implode(' ', $pagelinks); 

			if ($page != $numpages) {
				$nextpage = $page + 1;
				$URL->insert(array('p'=>$nextpage));
				?>

					<big><strong><a href="<?php echo $URL->generate(); ?>">Next <big>&raquo;</big></a></strong></big> <?php
			}	
		
			?>

				</div>
<?php
			
		}	
	
	}

	
	
	function comment_form ($commentdata) {
		// Comment data must at least contain an epobject_id.
		// Comment text is optional.
		// 'return_page' is either 'debate' or 'wran'.
		/* array (
			'epobject_id' => '7',
			'gid' => '2003-02-02.h34.2',
			'body' => 'My comment text is here.',
			'return_page' => 'debate'
		  )
		*/
		global $THEUSER, $this_page;

		if (!isset($commentdata['epobject_id']) || !is_numeric($commentdata['epobject_id'])) {
			$this->error_message("Sorry, we need an epobject id");
			return;
		}
		
		if (!$THEUSER->isloggedin()) {
			// The user is not logged in.
			
			// The URL of this page - we'll want to return here after joining/logging in.
			$THISPAGEURL = new URL($this_page);
			
			// The URLs to login / join pages.
			$LOGINURL = new URL('userlogin');
			$LOGINURL->insert(array('ret'=>$THISPAGEURL->generate().'#addcomment'));
			$JOINURL = new URL('userjoin');
			$JOINURL->insert(array('ret'=>$THISPAGEURL->generate().'#addcomment'));
			
			?>
				<p><a href="<?php echo $LOGINURL->generate(); ?>">Sign in</a> or <a href="<?php echo $JOINURL->generate(); ?>">join</a> to post a public annotation.</p>
<?php
			return;
			
		} else if (!$THEUSER->is_able_to('addcomment')) {
			// The user is logged in but not allowed to post a comment.

			?>
				<p>You are not allowed to post annotations.</p>
<?php
			return;
		}
		
		// We can post a comment...
			
		$ADDURL = new URL('addcomment');
		$RULESURL = new URL('houserules');
		?>
				<h4>Type your annotation</h4>
				<a name="addcomment"></a>
				
				<p><small>
Please read our <a href="<?php echo $RULESURL->generate(); ?>"><strong>House Rules</strong></a> before posting an annotation.
Annotations should be information that adds value to the contribution, not opinion, rants, or messages to a politician.
</small></p>

				<form action="<?php echo $ADDURL->generate(); ?>" method="post">
					<p><textarea name="body" rows="15" cols="55"><?php
		if (isset($commentdata['body'])) {
			echo htmlentities($commentdata['body']);
		}
		?></textarea></p>

					<p><input type="submit" value="Preview" class="submit">
<?php
		if (isset($commentdata['body'])) {
			echo '<input type="submit" name="submitcomment" value="Post" class="submit">';
		}
?>
</p>
					<input type="hidden" name="epobject_id" value="<?php echo $commentdata['epobject_id']; ?>">
					<input type="hidden" name="gid" value="<?php echo $commentdata['gid']; ?>">
					<input type="hidden" name="return_page" value="<?php echo $commentdata['return_page']; ?>">
				</form>
<?php
	}


	function display_commentreport ($data) {
		// $data has key value pairs.
		// Called from $COMMENT->display_report().
		
		if ($data['user_id'] > 0) {
			$USERURL = new URL('userview');
			$USERURL->insert(array('id'=>$data['user_id']));
			$username = '<a href="' . $USERURL->generate() . '">' . htmlentities($data['user_name']) . '</a>';
		} else {
			$username = htmlentities($data['user_name']);
		}
		?>	
				<div class="comment">
					<p class="credit"><strong>Annotation report</strong><br>
					<small>Reported by <?php echo $username; ?> on <?php echo $data['reported']; ?></small></p>

					<p><?php echo htmlentities($data['body']); ?></p>
				</div>
<?php
		if ($data['resolved'] != 'NULL') {
			?>
				<p>&nbsp;<br><em>This report has not been resolved.</em></p>
<?php
		} else {
			?>
				<p><em>This report was resolved on <?php echo $data['resolved']; ?></em></p>
<?php
			// We could link to the person who resolved it with $data['resolvedby'],
			// a user_id. But we don't have their name at the moment.
		}	
	
	}
	
	
	function display_commentreportlist ($data) {
		// For the admin section.
		// Gets an array of data from COMMENTLIST->render().
		// Passes it on to $this->display_table().

		if (count($data) > 0) {
		
			?>
			<h3>Reported annotations</h3>
<?php
			// Put the data in an array which we then display using $PAGE->display_table().
			$tabledata['header'] = array(
				'Reported by',
				'Begins...',
				'Reported on',
				''
			);
			
			$tabledata['rows'] = array();
			
			$EDITURL = new URL('admin_commentreport');
		
			foreach ($data as $n => $report) {
				
				if (!$report['locked']) {
					// Yes, we could probably cope if we just passed the report_id
					// through, but this isn't a public-facing page and life's 
					// easier if we have the comment_id too.
					$EDITURL->insert(array(
						'rid' => $report['report_id'],
						'cid' => $report['comment_id'],
					));
					$editlink = '<a href="' . $EDITURL->generate() . '">View</a>';
				} else {
					$editlink = 'Locked';
				}
				
				$body = trim_characters($report['body'], 0, 40);
								
				$tabledata['rows'][] = array (
					htmlentities($report['firstname'] . ' ' . $report['lastname']),
					htmlentities($body),
					$report['reported'],
					$editlink
				);
	
			}
			
			$this->display_table($tabledata);
			
		} else {
		
			print "<p>There are no outstanding annotation reports.</p>\n";
		}
	
	}
	


	function display_calendar_month ($month, $year, $dateArray, $page) {
		// From http://www.zend.com/zend/trick/tricks-Oct-2002.php
		// Adjusted for style, putting Monday first, and the URL of the page linked to.
		
		// Used in templates/html/hansard_calendar.php
		
		// $month and $year are integers.
		// $dateArray is an array of dates that should be links in this month.
		// $page is the name of the page the dates should link to.
	
		// Create array containing abbreviations of days of week.
		$daysOfWeek = array('Mon','Tue','Wed','Thu','Fri','Sat','Sun');
		
		// What is the first day of the month in question?
		$firstDayOfMonth = mktime(0,0,0,$month,1,$year);
		
		// How many days does this month contain?
		$numberDays = date('t',$firstDayOfMonth);
		
		// Retrieve some information about the first day of the
		// month in question.
		$dateComponents = getdate($firstDayOfMonth);
		
		// What is the name of the month in question?
		$monthName = $dateComponents['month'];
		
		// If this calendar is for this current, real world, month
		// we get the value of today, so we can highlight it.
		$nowDateComponents = getdate();
		if ($nowDateComponents['mon'] == $month && $nowDateComponents['year'] == $year) {
			$toDay = $nowDateComponents['mday'];
		} else {
			$toDay = '';
		}
		
		// What is the index value (0-6) of the first day of the
		// month in question.
		
		// Adjusted to cope with the week starting on Monday.
		$dayOfWeek = $dateComponents['wday'] - 1;
		
		// Adjusted to cope with the week starting on Monday.
		if ($dayOfWeek < 0) {
			$dayOfWeek = 6;
		}
		
		// Create the table tag opener and day headers
		
		$calendar  = "\t\t\t\t<div class=\"calendar\">\n";
		$calendar .= "\t\t\t\t<table border=\"0\">\n";
		$calendar .= "\t\t\t\t<caption>$monthName $year</caption>\n";
		$calendar .= "\t\t\t\t<thead>\n\t\t\t\t<tr>";
		
		// Create the calendar headers
		
		foreach($daysOfWeek as $day) {
			$calendar .= "<th>$day</th>";
		} 
		
		// Create the rest of the calendar
		
		// Initiate the day counter, starting with the 1st.
		
		$currentDay = 1;
		
		$calendar .= "</tr>\n\t\t\t\t</thead>\n\t\t\t\t<tbody>\n\t\t\t\t<tr>";
		
		// The variable $dayOfWeek is used to
		// ensure that the calendar
		// display consists of exactly 7 columns.
		
		if ($dayOfWeek > 0) { 
			$calendar .= "<td colspan=\"$dayOfWeek\">&nbsp;</td>"; 
		}
		
		$DAYURL = new URL($page);
		
		while ($currentDay <= $numberDays) {
		
			// Seventh column (Sunday) reached. Start a new row.
			
			if ($dayOfWeek == 7) {
			
				$dayOfWeek = 0;
				$calendar .= "</tr>\n\t\t\t\t<tr>";
			}
			
		
			// Is this day actually Today in the real world?
			// If so, higlight it.
			if ($currentDay == $toDay) {
				$calendar .= '<td class="on">';
			} else {
				$calendar .= '<td>';
			}

			// Is the $currentDay a member of $dateArray? If so,
			// the day should be linked.
			if (in_array($currentDay,$dateArray)) {
			
				$date = sprintf("%04d-%02d-%02d", $year, $month, $currentDay);
				
				$DAYURL->insert(array('d'=>$date));
				
				$calendar .= "<a href=\"" . $DAYURL->generate() . "\">$currentDay</a></td>";
				
				// $currentDay is not a member of $dateArray.
			
			} else {
			
				$calendar .= "$currentDay</td>";
			}
			
			// Increment counters
			
			$currentDay++;
			$dayOfWeek++;
		}
		
		// Complete the row of the last week in month, if necessary
		
		if ($dayOfWeek != 7) { 
		
			$remainingDays = 7 - $dayOfWeek;
			$calendar .= "<td colspan=\"$remainingDays\">&nbsp;</td>"; 
		}
		
		
		$calendar .= "</tr>\n\t\t\t\t</tbody>\n\t\t\t\t</table>\n\t\t\t\t</div> <!-- end calendar -->\n\n";
		
		return $calendar;
	
	}


	function display_table($data) {
		/* Pass it data to be displayed in a <table> and it renders it
			with stripes.
		
		$data is like (for example):
		array (
			'header' => array (
				'ID',
				'name'
			),
			'rows' => array (
				array (
					'37',
					'Guy Fawkes'
				),
				etc...
			)
		)
		*/

		?>
	<table border="1" cellpadding="3" cellspacing="0" width="90%">
<?php
		if (isset($data['header']) && count($data['header'])) {
			?>
	<thead>
	<tr><?php
			foreach ($data['header'] as $text) {
				?><th><?php echo $text; ?></th><?php
			}
			?></tr>
	</thead>
<?php
		}
		
		if (isset($data['rows']) && count($data['rows'])) {
			?>
	<tbody>
<?php
			foreach ($data['rows'] as $row) {
				?>
	<tr><?php
				foreach ($row as $text) {
					?><td><?php echo $text; ?></td><?php
				}
				?></tr>
<?php
			}
			?>
	</tbody>
<?php
		}
	?>
	</table>
<?php

	}
	
	
	
	function admin_menu () {
		// Returns HTML suitable for putting in the sidebar on Admin pages.
		global $this_page, $DATA;
		
		$pages = array ('admin_home', 
                'admin_comments','admin_trackbacks', 'admin_searchlogs', 'admin_popularsearches', 'admin_failedsearches',
                'admin_statistics', 
                'admin_commentreports', 'admin_glossary', 'admin_glossary_pending', 'admin_badusers',
                'admin_alerts', 'admin_photos', 'admin_mpurls'
                );
		
		$links = array();
	
		foreach ($pages as $page) {
			$title = $DATA->page_metadata($page, 'title');
			
			if ($page != $this_page) {
				$URL = new URL($page);
				$title = '<a href="' . $URL->generate() . '">' . $title . '</a>';
			} else {
				$title = '<strong>' . $title . '</strong>';
			}

			$links[] = $title;
		}
		
		$html = "<ul>\n";
		
		$html .= "<li>" . implode("</li>\n<li>", $links) . "</li>\n";
		
		$html .= "</ul>\n";
		
		return $html;
	}
}


$PAGE = new PAGE;

function display_stats_line($category, $blurb, $type, $inwhat, $afterstuff, $extra_info, $minister = false, $Lminister = false) {
	$return = false;
	if (isset($extra_info[$category]))
		$return = display_stats_line_house(1, $category, $blurb, $type, $inwhat, $extra_info, $minister, $afterstuff);
	if (isset($extra_info["L$category"]))
		$return = display_stats_line_house(2, "L$category", $blurb, $type, $inwhat, $extra_info, $Lminister, $afterstuff);
	return $return;
}
function display_stats_line_house($house, $category, $blurb, $type, $inwhat, $extra_info, $minister, $afterstuff) {
	if ($category == 'wrans_asked_inlastyear' || $category == 'debate_sectionsspoken_inlastyear' || $category =='comments_on_speeches' ||
		$category == 'Lwrans_asked_inlastyear' || $category == 'Ldebate_sectionsspoken_inlastyear' || $category =='Lcomments_on_speeches') {
		if ($extra_info[$category]==0) {
			$blurb = preg_replace('#<a.*?>#', '', $blurb);
			$inwhat = preg_replace('#<\/a>#', '', $inwhat);
		}
	}
	if ($house==2) $inwhat = str_replace('MP', 'Lord', $inwhat);
	print '<li>' . $blurb;
	print '<strong>' . $extra_info[$category];
	if ($type) print ' ' . make_plural($type, $extra_info[$category]);
	print '</strong>';
	print $inwhat;
	if ($minister===2) {
		print ' &#8212; Speakers/ deputy speakers do not ask written questions';
	} elseif ($minister)
		print ' &#8212; Ministers do not ask written questions';
	else {
		$type = ($house==1?'MP':($house==2?'Lord':'MLA'));
		if (!get_http_var('rem') && isset($extra_info[$category . '_quintile'])) {
			print ' &#8212; ';
			$q = $extra_info[$category . '_quintile'];
			if ($q == 0) {
				print 'well above average';
			} elseif ($q == 1) {
				print 'above average';
			} elseif ($q == 2) {
				print 'average';
			} elseif ($q == 3) {
				print 'below average';
			} elseif ($q == 4) {
				print 'well below average';
			} else {
				print '[Impossible quintile!]';
			}
			print ' amongst ';
			print $type . 's';
		} elseif (!get_http_var('rem') && isset($extra_info[$category . '_rank'])) {
			print ' &#8212; ';
			#if (isset($extra_info[$category . '_rank_joint']))
			#	print 'joint ';
			print make_ranking($extra_info[$category . '_rank']) . ' out of ' . $extra_info[$category . '_rank_outof'];
			print ' ' . $type . 's';
		}
	}
	print ".$afterstuff";
	return true;
}

function display_writetothem_numbers($year, $extra_info) {
	if (isset($extra_info["writetothem_responsiveness_notes_$year"])) {
	?><li>Responsiveness to messages sent via <a href="http://www.writetothem.com/stats/<?=$year?>/mps">WriteToThem.com</a> in <?=$year?>: <?=$extra_info["writetothem_responsiveness_notes_$year"]?>.</li><?
		return true;
	} elseif (isset($extra_info["writetothem_responsiveness_mean_$year"])) {
		$mean = $extra_info["writetothem_responsiveness_mean_$year"];

		$a = $extra_info["writetothem_responsiveness_fuzzy_response_description_$year"];
		if ($a == 'very low') $a = 'a very low';
		if ($a == 'low') $a = 'a low';
		if ($a == 'medium') $a = 'a medium';
		if ($a == 'high') $a = 'a high';
		if ($a == 'very high') $a = 'a very high';
		$extra_info["writetothem_responsiveness_fuzzy_response_description_$year"] = $a;

		return display_stats_line("writetothem_responsiveness_fuzzy_response_description_$year", 'Replied within 2 or 3 weeks to <a href="http://www.writetothem.com/stats/'.$year.'/mps" title="From WriteToThem.com">', "", "</a> <!-- Mean: " . $mean . " --> number of messages sent via WriteToThem.com during ".$year.", according to constituents", "", $extra_info);
	}

}


?>
