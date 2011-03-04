<?php
/*
Plugin Name: Featured Tag Widget
Plugin URI: http://wordpress.org/extend/plugins/featured-tag-widget/
Description: This widget plugin displays in your sidebar a list of posts (and much more) for a particular Tag. You can also add multiple instances of the widget.
Author: Andrea Developer
Version: 0.9.4
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
        $ftwp_options = get_option('widget_ftwp');
        if ( !isset($ftwp_options[$number]) )
                return;

        $customtext = $ftwp_options[$number]['customtext'];
		$featuredtag = $ftwp_options[$number]['tag'];
		//$nposts = $ftwp_options[$number]['nposts'];
        $nposts = empty($ftwp_options[$number]['nposts']) ? __('-1') : $ftwp_options[$number]['nposts'];		
		$showimage = $ftwp_options[$number]['showimage'];
		$showtitle = $ftwp_options[$number]['showtitle'];
		$showauthor = $ftwp_options[$number]['showauthor'];
		$invertorder = $ftwp_options[$number]['invertorder'];

		// To have custom default widget title insert YOUR_TITLE between ('') instead of ('Featured Tag') 
		// and move the # to comment the next line    
		#$title = empty($ftwp_options[$number]['title']) ? __('Featured Tag') : $ftwp_options[$number]['title'];
		$title = $ftwp_options[$number]['title'];		 


		//-------------------------------------------------- *  The loop START
			  
			echo $before_widget;
            if ($title){ 
					echo $before_title . $title . $after_title .'';
               } 			
			   
            // write Custom Text Area    
			if ($customtext){ ?>
				<div class="featured_tag_post"><?php echo $customtext; ?><br /></div>
			  <?php } 		   
			  		  		  		  
			// set $my_tag as featured tag
			 $all_tags = get_tags(); 
				foreach($all_tags as $the_tag):
					if ($the_tag->term_id==$featuredtag) {
					   $my_tag = $the_tag->slug;				   
					}		   
				endforeach;  		
			 
			// Set display posts order option: newest to oldest --> oldest to newest     
			if ($invertorder){             
				$my_ftwp_query = new WP_Query('tag='.$my_tag.'&posts_per_page='.$nposts.'&order=ASC'); 
			} else { 
				$my_ftwp_query = new WP_Query('tag='.$my_tag.'&posts_per_page='.$nposts.'&order=DESC');
			}
		
			if (have_posts()) : while ($my_ftwp_query->have_posts()) : $my_ftwp_query->the_post();	?>
                    
                    <?php if ($showimage){
								$thumb_url = get_thumb_url(); 
									if ($thumb_url){ ?>
										<div class="thumbnail">
										<a href="<?php the_permalink(); ?>"><img src="<?php echo $thumb_url; ?>"  alt="<?php the_title(); ?>" title="<?php the_title(); ?>" /> </a><br />
										</div>
						 	<?php } ?>
                	<?php } ?>
                        
                	<?php if (($showtitle)or($showauthor)){ ?>
                    			<div class="featured_tag_post"><ul><li>
                        		<?php if ($showtitle){ ?>
                            			<span class="post-title"><a href="<?php the_permalink(); ?>"> <?php the_title(); ?> </a></span><br />
                        		<?php } ?>
                        		<?php if ($showauthor){ ?>
                            			<span class="byline"><em><?php the_author_posts_link(); ?></em></span><br />
                       			<?php } ?>
                        		<!--<br />-->         
                                </li></ul></div>
					<?php } ?>

			  <?php endwhile; else: ?>
              <?php _e('No posts - Featured Tag.'); ?>
              <?php endif; ?>
              
			<!--  <br />-->
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
        $ftwp_options = get_option('widget_ftwp');
        if ( !is_array($ftwp_options) )
                $ftwp_options = array();

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
                                        unset($ftwp_options[$widget_number]);
                        }
                }

                foreach ( (array) $_POST['widget-featured-tag'] as $widget_number => $widget_ftwp_instance ) {
                        // compile data from $widget_ftwp_instance
                        if ( !isset($widget_ftwp_instance['tag']) && isset($ftwp_options[$widget_number]) ) // user clicked cancel
                                continue;
                        
                        $title = strip_tags(stripslashes($widget_ftwp_instance['title'])); 
						$customtext = strip_tags(stripslashes($widget_ftwp_instance['customtext'])); 
						$featuredtag = wp_specialchars( $widget_ftwp_instance['tag'] );
						$nposts = $widget_ftwp_instance['nposts'];	
						$nposts = preg_replace("/[^0-9]/i", "", $nposts);  // numbers only
						$showimage = isset( $widget_ftwp_instance['showimage'] ) ? $widget_ftwp_instance['showimage'] : 0;
						$showtitle = isset( $widget_ftwp_instance['showtitle'] ) ? $widget_ftwp_instance['showtitle'] : 0;	
						$showauthor = isset( $widget_ftwp_instance['showauthor'] ) ? $widget_ftwp_instance['showauthor'] : 0;
						$invertorder = isset( $widget_ftwp_instance['invertorder'] ) ? $widget_ftwp_instance['invertorder'] : 0;						
													 						
                        $ftwp_options[$widget_number] = array( 'customtext' => $customtext, 'tag' => $featuredtag, 'title' => $title, 'nposts' => $nposts, 'showimage' => $showimage, 'showtitle' => $showtitle, 'showauthor' => $showauthor, 'invertorder' => $invertorder );  						// Even simple widgets should store stuff in array, rather than in scalar
                }

                update_option('widget_ftwp', $ftwp_options);
                $updated = true; // So that we don't go through this more than once
        }


        // Here we echo out the form
        if ( -1 == $number ) { // We echo out a template for a form which can be converted to a specific form later via JS
                $featuredtag = '';
                $number = '%i%';
        } else {
                $title = htmlspecialchars($ftwp_options[$number]['title'], ENT_QUOTES);
				$customtext = htmlspecialchars($ftwp_options[$number]['customtext'], ENT_QUOTES);
                $featuredtag = attribute_escape($ftwp_options[$number]['tag']);				
                $featuredtag = htmlspecialchars($ftwp_options[$number]['tag'], ENT_QUOTES);
				$nposts = stripslashes($ftwp_options[$number]['nposts']);
				$showimage = (bool)($ftwp_options[$number]['showimage']);
				$showtitle = (bool)($ftwp_options[$number]['showtitle']);				
				$showauthor = (bool)($ftwp_options[$number]['showauthor']);
				$invertorder = (bool)($ftwp_options[$number]['invertorder']);
												
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
    
    <p style="text-align:left;">
       <label for="widget-featured-tag-customtext-<?php echo $number; ?>"><?php echo __('Custom Text:'); ?>
       <textarea style="width: 100%; height: 80px;" id="widget-featured-tag-customtext-<?php echo $number; ?>" name="widget-featured-tag[<?php echo $number; ?>][customtext]"><?php echo $customtext; ?></textarea>
        </label>
    </p>
        
	<?php $all_tags = get_tags('orderby=name&order=ASC'); ?>      		    
	<?php if ( $all_tags ) {  ?>
    
             <label for="widget-featured-tag-tag-<?php echo $number; ?>">
			 Use the dropdown menu to choose the Tag to pull the posts from.<br />
             The informations shown refers to Tag:  Name - ID - (total posts)<br />
			 <?php echo __('Tag:', 'widgets'); ?>
             <select id="widget-featured-tag-tag-<?php echo $number; ?>" name="widget-featured-tag[<?php echo $number; ?>][tag]">		
                <?php foreach($all_tags as $the_tag):?>
                	<?php $postcounter = $the_tag->count;?>
                   <option <?php if ($the_tag->term_id==$featuredtag) echo 'selected="selected"';?> value="<?php echo $the_tag->term_id;?>"><?php echo $the_tag->name; ?> - <?php echo $the_tag->term_id;?> - (<?php echo $postcounter;?>)</option>
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

            <p style="text-align:left;">
               <label for="widget-featured-tag-order-<?php echo $number; ?>"><?php echo __('Invert Posts Order (newest to oldest --> oldest to newest):'); ?>       
               <input type="checkbox" id="widget-featured-tag-order-<?php echo $number; ?>" name="widget-featured-tag[<?php echo $number; ?>][invertorder]" 
                <?php checked( $invertorder,true ); ?>  /></input>
                </label>
            </p>             
        
	<?php } else { echo 'Tag: No Tag found ( <i>Insert at least one Tag in any post</i> )'; }?>           
    <input type="hidden" id="widget-featured-tag-submit-<?php echo $number; ?>" name="widget-featured-tag[<?php echo $number; ?>][submit]" value="1" />
<?php 
}



//-------------------------------------------------- * Registers each instance of our widget on startup.
function widget_ftwp_register() {
        if ( !$ftwp_options = get_option('widget_ftwp') )
                $ftwp_options = array();

        $widget_ops = array('classname' => 'widget_ftwp', 'description' => __('The posts for a particular Tag.'));
        $control_ops = array('width' => 400, 'height' => 350, 'id_base' => 'ftwp');
        $name_ftwp = __('Featured Tag Widget');

        $registered = false;
        foreach ( array_keys($ftwp_options) as $o ) {
                // Old widgets can have null values for some reason
                if ( !isset($ftwp_options[$o]['tag']) ) // we used 'tag' above in our example.  Replace with whatever your real data are.
                        continue;

                // $id should look like {$id_base}-{$o}
                $id = "ftwp-$o"; // Never never never translate an id
                $registered = true;
                wp_register_sidebar_widget( $id, $name_ftwp, 'widget_ftwp', $widget_ops, array( 'number' => $o ) );
                wp_register_widget_control( $id, $name_ftwp, 'widget_ftwp_control', $control_ops, array( 'number' => $o ) );
        }

        // If there are none, we register the widget's existance with a generic template
        if ( !$registered ) {
                wp_register_sidebar_widget( 'ftwp-1', $name_ftwp, 'widget_ftwp', $widget_ops, array( 'number' => -1 ) );
                wp_register_widget_control( 'ftwp-1', $name_ftwp, 'widget_ftwp_control', $control_ops, array( 'number' => -1 ) );
        }
}

// This is important
add_action( 'widgets_init', 'widget_ftwp_register' );



//-------------------------------------------------- * Shows image thumbnail (for width & height refer to your wordpress/admin/settings/media)

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
