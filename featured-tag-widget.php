<?php
/*
Plugin Name: Featured Tag Widget
Plugin URI: http://wordpress.org/extend/plugins/featured-tag-widget/
Description: This widget plugin shows a list of posts for a particular Tag in your sidebar with many options. You can also add multiple instances of this widget. This plugin gives you full control on the widget with these options: custom widget Title (or no Title), the Featured Tag selection (obviously required), how many posts for the featured Tag to show for each instance of the widget, the posts informations to show (image thumbnail, title, author). 
Author: Andrea Developer
Version: 0.6
Author URI: http://wordpress.org/extend/plugins/featured-tag-widget/

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St - 5th Floor, Boston, MA  02110-1301, USA.

*/



//-------------------------------------------------- * Displays widget in Sidebar
 /* 
 * Supports multiple widgets.
 * @param array $args Widget arguments.
 * @param array|int $widget_args Widget number. Which of the several widgets of this type do we mean.
 */
function widget_ftwp( $args, $widget_args = 1 ) {
        extract( $args, EXTR_SKIP );
        if ( is_numeric($widget_args) )
                $widget_args = array( 'number' => $widget_args );
        $widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
        extract( $widget_args, EXTR_SKIP );

        // Data should be stored as array:  array( number => data for that instance of the widget, ... )
        $options = get_option('widget_ftwp');
        if ( !isset($options[$number]) )
                return;

        $featuredtag = $options[$number]['tag'];
		//$nposts = $options[$number]['nposts'];
        $nposts = empty($options[$number]['nposts']) ? __('-1') : $options[$number]['nposts'];		
		$showimage = $options[$number]['showimage'];
		$showtitle = $options[$number]['showtitle'];
		$showauthor = $options[$number]['showauthor'];

		// To have custom default widget title insert YOUR_TITLE between ('') instead of ('Featured Tag') 
		// and move the comment to the next line    
		#$title = empty($options[$number]['title']) ? __('Featured Tag') : $options[$number]['title'];
		$title = $options[$number]['title'];		 


		//-------------------------------------------------- *  The loop START
			  
			  echo $before_widget;
               if ($title){ 
					echo $before_title . $title . $after_title .'';
               } 			  
			  		  		  		  
			// set $my_tag as featured tag
			$all_tags = get_tags(); 
				foreach($all_tags as $the_tag):
					if ($the_tag->term_id==$featuredtag) {
					   $my_tag = $the_tag->slug;				   
					}		   
				endforeach;  		
			 
			$my_query = new WP_Query('tag='.$my_tag.'&posts_per_page='.$nposts); 
				if (have_posts()) : while ($my_query->have_posts()) : $my_query->the_post(); 			 		 	
				?>
                    
                    <?php if ($showimage){ ?>
						<?php $thumb_url = get_thumb_url(); 
						if ($thumb_url){ ?>
							<div class="thumbnail"><img src="<?php echo $thumb_url; ?>" alt="<?php the_title(); ?>" /></div>
						 <?php } ?>
                <?php } ?>
                        
                <?php if (($showtitle)or($showauthor)){ ?>
                    <div class="featured_tag_post">
                        <?php if ($showtitle){ ?>
                            <strong class="post-title"><a href="<?php the_permalink(); ?>"> <?php the_title(); ?> </a></strong><br />
                        <?php } ?>
                        <?php if ($showauthor){ ?>
                            <span class="byline"><em><?php the_author_posts_link(); ?></em></span><br />
                        <?php } ?>
                        <br />             
                    </div>
				<?php } ?>

			  <?php endwhile; else: ?>
              <?php _e('No posts - Featured Tag.'); ?>
              <?php endif; ?>
              
           
          <?php
          echo $after_widget;
		  
		//-------------------------------------------------- *  The loop END
}



//-------------------------------------------------- * Displays instance of widget in Admin Widgets: The form
//
// Also updates the data after a POST submit.
// @param array|int $widget_args Widget number. Which of the several widgets of this type do we mean.

function widget_ftwp_control( $widget_args = 1 ) {
        global $wp_registered_widgets;
        static $updated = false; // Whether or not we have already updated the data after a POST submit

        if ( is_numeric($widget_args) )
                $widget_args = array( 'number' => $widget_args );
        $widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
        extract( $widget_args, EXTR_SKIP );

        // Data should be stored as array:  array( number => data for that instance of the widget, ... )
        $options = get_option('widget_ftwp');
        if ( !is_array($options) )
                $options = array();

        // We need to ---UPDATE--- the data
        if ( !$updated && !empty($_POST['sidebar']) ) {
                // Tells us what sidebar to put the data in
                $sidebar = (string) $_POST['sidebar'];

                $sidebars_widgets = wp_get_sidebars_widgets();
                if ( isset($sidebars_widgets[$sidebar]) )
                        $this_sidebar =& $sidebars_widgets[$sidebar];
                else
                        $this_sidebar = array();

                foreach ( $this_sidebar as $_widget_id ) {
                        // Remove all widgets of this type from the sidebar.  We'll add the new data in a second.  This makes sure we don't get any duplicate data
                        // since widget ids aren't necessarily persistent across multiple updates
                        if ( 'widget_ftwp' == $wp_registered_widgets[$_widget_id]['callback'] && isset($wp_registered_widgets[$_widget_id]['params'][0]['number']) ) {
                                $widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
                                if ( !in_array( "ftwp-$widget_number", $_POST['widget-id'] ) ) // the widget has been removed. "ftwp-$widget_number" is "{id_base}-{widget_number}
                                        unset($options[$widget_number]);
                        }
                }

                foreach ( (array) $_POST['widget-featured-tag'] as $widget_number => $widget_ftwp_instance ) {
                        // compile data from $widget_ftwp_instance
                        if ( !isset($widget_ftwp_instance['tag']) && isset($options[$widget_number]) ) // user clicked cancel
                                continue;
                        $featuredtag = wp_specialchars( $widget_ftwp_instance['tag'] );
                        $title = strip_tags(stripslashes($widget_ftwp_instance['title'])); 										 
						$nposts = $widget_ftwp_instance['nposts'];	
						$nposts = preg_replace("/[^0-9]/i", "", $nposts);  // numbers only
						$showimage = isset( $widget_ftwp_instance['showimage'] ) ? $widget_ftwp_instance['showimage'] : 0;
						$showtitle = isset( $widget_ftwp_instance['showtitle'] ) ? $widget_ftwp_instance['showtitle'] : 0;	
						$showauthor = isset( $widget_ftwp_instance['showauthor'] ) ? $widget_ftwp_instance['showauthor'] : 0;						
													 						
                        $options[$widget_number] = array( 'tag' => $featuredtag, 'title' => $title, 'nposts' => $nposts, 'showimage' => $showimage, 'showtitle' => $showtitle, 'showauthor' => $showauthor );  						// Even simple widgets should store stuff in array, rather than in scalar
                }

                update_option('widget_ftwp', $options);
                $updated = true; // So that we don't go through this more than once
        }


        // Here we echo out the form
        if ( -1 == $number ) { // We echo out a template for a form which can be converted to a specific form later via JS
                $featuredtag = '';
                $number = '%i%';
        } else {
                $title = htmlspecialchars($options[$number]['title'], ENT_QUOTES);
                $featuredtag = attribute_escape($options[$number]['tag']);				
                $featuredtag = htmlspecialchars($options[$number]['tag'], ENT_QUOTES);
				$nposts = stripslashes($options[$number]['nposts']);
				$showimage = (bool)($options[$number]['showimage']);
				$showtitle = (bool)($options[$number]['showtitle']);				
				$showauthor = (bool)($options[$number]['showauthor']);
												
				// Force missing data form field 
//                if (!$title)
//                  $title = 'Featured Tag';			  
//                if (!$nposts)
//                  $nposts = '10';
				  				  
        }

        // The form has inputs with names like widget-featured-tag[$number][tag] so that all data for that instance of
        // the widget are stored in one $_POST variable: $_POST['widget-featured-tag'][$number]
	?>
    <p>
       <label for="widget-featured-tag-title-<?php echo $number; ?>"><?php echo __('Widget Title:'); ?>
       <input style="width: 200px;" id="widget-featured-tag-title-<?php echo $number; ?>" name="widget-featured-tag[<?php echo $number; ?>][title]" type="text" value="<?php echo $title; ?>" />
        </label>
    </p>
    <p>
	<?php $all_tags = get_tags('orderby=name&order=ASC'); ?>      		    
	<?php if ( $all_tags ) {  ?>
		 <label for="widget-featured-tag-tag-<?php echo $number; ?>"><?php echo __('Tag:', 'widgets'); ?>
         <select id="widget-featured-tag-tag-<?php echo $number; ?>" name="widget-featured-tag[<?php echo $number; ?>][tag]">		
            <?php foreach($all_tags as $the_tag):?>
               <option <?php if ($the_tag->term_id==$featuredtag) echo 'selected="selected"';?> value="<?php echo $the_tag->term_id;?>"><?php echo $the_tag->name; ?> - ID <?php echo $the_tag->term_id;?></option>
            <?php endforeach;?>
         </select>
        </label>                                
        </p> 
        <p style="text-align:left;">
           <label for="widget-featured-tag-n-<?php echo $number; ?>"><?php echo __('Number of posts to show:'); ?>
           <input size="5" id="widget-featured-tag-n-<?php echo $number; ?>" value="<?php echo $nposts; ?>" name="widget-featured-tag[<?php echo $number; ?>][nposts]"></input> (<i>empty = all</i>)
            </label>
        </p> 
        <p style="text-align:left;">
           <label for="widget-featured-tag-show-image-<?php echo $number; ?>"><?php echo __('Show Posts Images Thumb:'); ?>       
           <input type="checkbox" id="widget-featured-tag-show-image-<?php echo $number; ?>" name="widget-featured-tag[<?php echo $number; ?>][showimage]" 
            <?php checked( $showimage,true ); ?>  /></input> (<i>if present</i>)
            </label>
        </p>    
        <p style="text-align:left;">
           <label for="widget-featured-tag-show-title-<?php echo $number; ?>"><?php echo __('Show Posts Titles:'); ?>       
           <input type="checkbox" id="widget-featured-tag-show-title-<?php echo $number; ?>" name="widget-featured-tag[<?php echo $number; ?>][showtitle]" 
            <?php checked( $showtitle,true ); ?>  /></input>    
            </label>
        </p>     
        <p style="text-align:left;">
           <label for="widget-featured-tag-show-author-<?php echo $number; ?>"><?php echo __('Show Posts Authors:'); ?>       
           <input type="checkbox" id="widget-featured-tag-show-author-<?php echo $number; ?>" name="widget-featured-tag[<?php echo $number; ?>][showauthor]" 
            <?php checked( $showauthor,true ); ?>  /></input>
            </label>
        </p>    
	<?php } else { echo 'Tag: No Tag found ( <i>Insert at least one Tag in any post</i> )'; }?>           
    <input type="hidden" id="widget-featured-tag-submit-<?php echo $number; ?>" name="widget-featured-tag[<?php echo $number; ?>][submit]" value="1" />
<?php 
}



//-------------------------------------------------- * Registers each instance of our widget on startup.
function widget_ftwp_register() {
        if ( !$options = get_option('widget_ftwp') )
                $options = array();

        $widget_ops = array('classname' => 'widget_ftwp', 'description' => __('A list of posts for a particular tag.'));
        $control_ops = array('width' => 400, 'height' => 350, 'id_base' => 'ftwp');
        $name = __('Featured Tag');

        $registered = false;
        foreach ( array_keys($options) as $o ) {
                // Old widgets can have null values for some reason
                if ( !isset($options[$o]['tag']) ) // we used 'tag' above in our example.  Replace with whatever your real data are.
                        continue;

                // $id should look like {$id_base}-{$o}
                $id = "ftwp-$o"; // Never never never translate an id
                $registered = true;
                wp_register_sidebar_widget( $id, $name, 'widget_ftwp', $widget_ops, array( 'number' => $o ) );
                wp_register_widget_control( $id, $name, 'widget_ftwp_control', $control_ops, array( 'number' => $o ) );
        }

        // If there are none, we register the widget's existance with a generic template
        if ( !$registered ) {
                wp_register_sidebar_widget( 'ftwp-1', $name, 'widget_ftwp', $widget_ops, array( 'number' => -1 ) );
                wp_register_widget_control( 'ftwp-1', $name, 'widget_ftwp_control', $control_ops, array( 'number' => -1 ) );
        }
}

// This is important
add_action( 'widgets_init', 'widget_ftwp_register' );



//-------------------------------------------------- * Shows image thumbnail(for width & height refer to your wordpress/admin/settings/media)
function get_thumb_url() { 
  global $post;

  $attargs = array(
                   'post_type' => 'attachment',
                   'numberposts' => null,
                   'post_status' => null,
                   'post_parent' => $post->ID
                   );

  $attachments = get_posts($attargs);

  if ($attachments)
    return wp_get_attachment_thumb_url($attachments[0]->ID);

  return '';
}
?>
