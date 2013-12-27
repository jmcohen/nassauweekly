<?php
/*
Plugin Name: Recent Tweets Enhanced Widget 2.3
Plugin URI: http://www.wittydiary.com
Description: Display your most recent Tweets in a sidebar widget easily. Now, you can show from 1 up to 20 Tweets! Blends in with your template automatically. To install this plugin, activate it and then go to Appearance > Widgets. Drag the widget 'Recent Tweets Enhanced' to the sidebar. To change the settings, simply fill in required information on the widget itself.
Version: 2.3
Author: WittyBlogger
Author URL: http://www.wittydiary.com
*/

add_action("widgets_init", array('Recent_Tweets_Enhanced', 'register'));
register_activation_hook( __FILE__, array('Recent_Tweets_Enhanced', 'activate'));
register_deactivation_hook( __FILE__, array('Recent_Tweets_Enhanced', 'deactivate'));
class Recent_Tweets_Enhanced {
  function activate(){
    $data = array('titletweets' => 'Recent Tweets', 'option1' => '', 'option2' => '3', 'option3' => 'No', 'option4' => 'Yes');
    if ( ! get_option('recent_tweets_enhanced')){
      add_option('recent_tweets_enhanced' , $data);
    } else {
      update_option('recent_tweets_enhanced' , $data);
    }
  }
  
 function control(){
  $data = get_option('recent_tweets_enhanced');
  ?>
  
  <!-- SETTINGS -->
  
    <p><label>Title: <input name="recent_tweets_titletweets"
type="text" value="<?php echo $data['titletweets']; ?>" /></label></p>
  <p><label>Your Twitter Username:<br />@<input name="recent_tweets_option1"
type="text" value="<?php echo $data['option1']; ?>" /></label></p>
  <p><label>Choose # of Tweets to Display:</label> 
  <select name="recent_tweets_option2">
  <option <?php if ($data['option2'] == 1){echo 'selected="selected"';} ?>>1</option>
  <option <?php if ($data['option2'] == 2){echo 'selected="selected"';} ?>>2</option>
  <option <?php if ($data['option2'] == 3){echo 'selected="selected"';} ?>>3</option>
  <option <?php if ($data['option2'] == 4){echo 'selected="selected"';} ?>>4</option>
  <option <?php if ($data['option2'] == 5){echo 'selected="selected"';} ?>>5</option>
  <option <?php if ($data['option2'] == 6){echo 'selected="selected"';} ?>>6</option>
  <option <?php if ($data['option2'] == 7){echo 'selected="selected"';} ?>>7</option>
  <option <?php if ($data['option2'] == 8){echo 'selected="selected"';} ?>>8</option>
  <option <?php if ($data['option2'] == 9){echo 'selected="selected"';} ?>>9</option>
  <option <?php if ($data['option2'] == 10){echo 'selected="selected"';} ?>>10</option>
  <option <?php if ($data['option2'] == 11){echo 'selected="selected"';} ?>>11</option>
  <option <?php if ($data['option2'] == 12){echo 'selected="selected"';} ?>>12</option>
  <option <?php if ($data['option2'] == 13){echo 'selected="selected"';} ?>>13</option>
  <option <?php if ($data['option2'] == 14){echo 'selected="selected"';} ?>>14</option>
  <option <?php if ($data['option2'] == 15){echo 'selected="selected"';} ?>>15</option>
  <option <?php if ($data['option2'] == 16){echo 'selected="selected"';} ?>>16</option>
  <option <?php if ($data['option2'] == 17){echo 'selected="selected"';} ?>>17</option>
  <option <?php if ($data['option2'] == 18){echo 'selected="selected"';} ?>>18</option>
  <option <?php if ($data['option2'] == 19){echo 'selected="selected"';} ?>>19</option>
  <option <?php if ($data['option2'] == 20){echo 'selected="selected"';} ?>>20</option>
</select></p>
<p><label>Allow Visitors To Follow You?</label> 
  <select name="recent_tweets_option3">
  <option <?php if ($data['option3'] == "Yes"){echo 'selected="selected"';} ?>>Yes</option>
  <option <?php if ($data['option3'] == "No"){echo 'selected="selected"';} ?>>No</option>
</select></p>
<p><label>Credit Us? (We'll Love You!)</label>
  <select name="recent_tweets_option4">
  <option <?php if ($data['option4'] == "Yes"){echo 'selected="selected"';} ?>>Yes</option>
  <option <?php if ($data['option4'] == "No"){echo 'selected="selected"';} ?>>No</option>
</select></p>

  <?php
   if (isset($_POST['recent_tweets_option1'])){
   	$data['titletweets'] = attribute_escape($_POST['recent_tweets_titletweets']);
    $data['option1'] = attribute_escape($_POST['recent_tweets_option1']);
    $data['option2'] = attribute_escape($_POST['recent_tweets_option2']);
    $data['option3'] = attribute_escape($_POST['recent_tweets_option3']);
    $data['option4'] = attribute_escape($_POST['recent_tweets_option4']);
    update_option('recent_tweets_enhanced', $data);
  }
}


  function deactivate(){
    delete_option('recent_tweets_enhanced');
  }
  function widget($args){
  	$data = get_option('recent_tweets_enhanced');
    echo $args['before_widget'];
    echo $args['before_title'] . $data['titletweets'] . $args['after_title'];
    echo '<style type="text/css">
#twitter_update_list li {
list-style-type: none;
padding-top: 10px;
}</style>
<div id="twitter_update_list">
<script type="text/javascript" src="http://twitter.com/javascripts/blogger.js"> </script>
<script type="text/javascript" src="http://twitter.com/statuses/user_timeline/'.$data['option1'].'.json?callback=twitterCallback2&count='.$data['option2'].'"></script></div><br />';

$randomnumber = mt_rand(1, 100);

$thelink = "how to make money";


	if ($data['option3'] == "Yes"){
echo '<a href="http://twitter.com/'.$data['option1'].'">Follow @'.$data['option1'].'</a><br />';} else {}
		
if ($data['option4'] == "Yes"){
echo 'Powered By: <a href="http://wittydiary.com/">'.$thelink.'</a>';} else {}

echo $args['after_widget'];
  }
function register(){
    register_sidebar_widget('Recent Tweets Enhanced', array('Recent_Tweets_Enhanced', 'widget'));
    register_widget_control('Recent Tweets Enhanced', array('Recent_Tweets_Enhanced', 'control'));
  }
}

//tested

?>