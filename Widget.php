<?php

class OHSNLWidget extends WP_Widget {

    /**
     * Sets up the widgets name etc
     */
    public function __construct() {
        $widget_ops = array(
            'classname' => 'ohs_newsletter_widget',
            'description' => 'OHS Newsletter double opt in widget',
        );
        parent::__construct( 'ohs_newsletter_widget', 'OHS Newsletter', $widget_ops );
    }

    /**
     * Outputs the content of the widget
     *
     * @param array $args
     * @param array $instance
     */
    public function widget( $args, $instance ) {
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script('ohs-newsletter', plugins_url('/ohsnewsletter/js/ohsnewsletter.js'));


        wp_localize_script( 'ohs-newsletter', 'ohsNL',
            array('API_sub' =>  home_url() . '/wp-json/ohsnewsletter/v1/subscribe/', 'security' =>  wp_create_nonce( OHS_NL_NONCE_STRING )));

        echo $args['before_widget'];
        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
        }
        ?>
        <div class="ohsi-sendgrid-widget">
            <div class="ohsi-msg"></div>
            <div class="ohsi-sendgrid-form">
                <label>
                    First Name
                    <input type="text" class="ohsnl-fn">
                </label>
                <label>
                    Last Name
                    <input type="text" class="ohsnl-ln">
                </label>
                <label>
                    Email
                    <input type="text" class="ohsnl-em" placeholder="you@example.com">
                </label>
                <input type="submit" class="ohssubmit" value="Subscribe">
            </div>
        </div>
        <?php

        echo $args['after_widget'];
    }

    /**
     * Outputs the options form on admin
     *
     * @param array $instance The widget options
     */
    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : "";
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <?php
    }

    /**
     * Processing widget options on save
     *
     * @param array $new_instance The new options
     * @param array $old_instance The previous options
     */
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

        return $instance;
    }
}
