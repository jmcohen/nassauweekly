<?php

/*
Plugin Name: Facebook Like
Plugin URI: http://protishobdo.com/blog/facebook-like-a-simple-wordpress-plugin/
Description: Include facebook Like Button in Index and single page!
Version:1.0.199
Author: Mazharul anwar
Author URI: http://protishobdo.com
*/



/*



    Copyright 2010 by Mazharul Anwar <mazharul2007@gmail.com>



    This program is free software: you can redistribute it and/or modify

    it under the terms of the GNU General Public License as published by

    the Free Software Foundation, either version 3 of the License, or

    (at your option) any later version.



    This program is distributed in the hope that it will be useful,

    but WITHOUT ANY WARRANTY; without even the implied warranty of

    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the

    GNU General Public License for more details.



    You should have received a copy of the GNU General Public License

    along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/



/*



Date		Rev	Modification

04/22/10	1.0.199 Initital version	

 */

 

function facebook_lite_css() {



	$authorboxcss = WP_CONTENT_URL.'/plugins/'.plugin_basename(dirname(__FILE__)).'/facebook_lite_css.css';



	echo '<link rel="stylesheet" type="text/css" href="' . $authorboxcss . '" media="screen" />';



}



 add_action('wp_head', 'facebook_lite_css');

 add_action('the_content', 'add_facebook_lite');

 function add_facebook_lite($content=''){

	

?>	

<iframe src="http://www.facebook.com/plugins/like.php?href=<?php the_permalink();?>&amp;layout=standard&amp;show_faces=true&amp;width=450&amp;action=like&amp;colorscheme=light" scrolling="no" frameborder="0" allowTransparency="true" style="border:none; overflow:hidden; width:450px; height:60px"></iframe> 

<?  

   

	 return $content;

}

 

  

 