<?php

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class EAFullCalendar
 */
class EAFullCalendar
{

    /**
     * @var EAOptions
     */
    protected $options;

    /**
     * @var EADBModels
     */
    protected $models;

    /**
     * @var EADateTime
     */
    protected $datetime;

    /**
     * @param EADBModels $models
     * @param EAOptions $options
     * @param $datetime
     */
    function __construct($models, $options, $datetime)
    {
        $this->options  = $options;
        $this->models   = $models;
        $this->datetime = $datetime;
    }

    public function init()
    {
        // register JS
         add_action('wp_enqueue_scripts', array($this, 'init_scripts'));
        // add_action( 'admin_enqueue_scripts', array( $this, 'init' ) );

        // add shortcode standard
        add_shortcode('ea_full_calendar', array($this, 'ea_full_calendar'));
    }

    public function init_scripts()
    {
        // bootstrap script
        wp_register_script(
            'ea-full-calendar',
            EA_PLUGIN_URL . 'js/libs/fullcalendar/fullcalendar.min.js',
            array('jquery', 'ea-momentjs', 'wp-api'),
            '2.0.0',
            true
        );

        wp_register_style(
            'ea-full-calendar-style',
            EA_PLUGIN_URL . 'js/libs/fullcalendar/fullcalendar.css'
        );

        // admin style
        wp_register_style(
            'ea-full-calendar-custom-css',
            EA_PLUGIN_URL . 'css/full-calendar.css'
        );
    }

    public function ea_full_calendar($atts)
    {

        $code_params = shortcode_atts(array(
            'location'             => null,
            'service'              => null,
            'worker'               => null,
            'start_of_week'        => get_option('start_of_week', 0),
            'rtl'                  => '0',
            'default_date'         => date('Y-m-d'),
            'min_date'             => null,
            'max_date'             => null,
            'show_remaining_slots' => '0',
            'show_week'            => '0',
            'title_field'          => 'name',
            'default_view'         => 'month', // basicWeek, basicDay, agendaDay, agendaWeek
            'views'                => 'month,basicWeek,basicDay',
            'day_names_short'      => 'Sun,Mon,Tue,Wed,Thu,Fri,Sat',
            'day_names'            => 'Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'month_names_short'    => 'Jan,Feb,Mar,Apr,May,Jun,Jul,Aug,Sep,Oct,Nov,Dec',
            'month_names'          => 'January,February,March,April,May,June,July,August,September,October,November,December'
        ), $atts);

        // scripts that are going to be used
        wp_enqueue_script('underscore');
        wp_enqueue_script('ea-validator');
        wp_enqueue_script('ea-full-calendar');

        wp_enqueue_style('ea-full-calendar-style');
        wp_enqueue_style('ea-full-calendar-custom-css');

        $id = uniqid();

        $show_week_numbers = $code_params['show_week'] === '1' ? 'true' : 'false';
        $is_rtl = $code_params['rtl'] === '1' ? 'true' : 'false';

        /**
         * Convert string: 'Sun,Mon,Tue,Wed,Thu,Fri,Sat' to string
         * "['Sun','Mon','Tue','Wed','Thu','Fri','Sat']" etc
         */
        $day_names_short = $this->convert_csv_to_js_array_of_strings($code_params['day_names_short']);
        $day_names = $this->convert_csv_to_js_array_of_strings($code_params['day_names']);
        $month_names_short = $this->convert_csv_to_js_array_of_strings($code_params['month_names_short']);
        $month_names = $this->convert_csv_to_js_array_of_strings($code_params['month_names']);

        $script = <<<EOT
  jQuery(document).ready(function() {

    jQuery('#ea-full-calendar-{$id}').fullCalendar({
      header: {
        left: 'prev,next today',
        center: 'title',
        right: '{$code_params['views']}'
      },
      dayNamesShort: {$day_names_short},
      dayNames: {$day_names},
      monthNamesShort: {$month_names_short},
      monthNames: {$month_names},
      isRTL: {$is_rtl},
      defaultView: '{$code_params['default_view']}',
      showNonCurrentDates: false,
      weekNumbers: {$show_week_numbers},
      firstDay: {$code_params['start_of_week']},
      defaultDate: '{$code_params['default_date']}',
      navLinks: true, // can click day/week names to navigate views
      editable: true,
      eventLimit: true, // allow "more" link when too many events
      events: {
        url: wpApiSettings.root + 'easy-appointments/v1/appointments',
        type: 'GET',
        data: {
          _wpnonce: wpApiSettings.nonce, 
          location: '{$code_params['location']}',
          service: '{$code_params['service']}',
          worker: '{$code_params['worker']}',
          title_field: '{$code_params['title_field']}',
        },
        error: function() {
          alert('there was an error while fetching events!');
        },
        textColor: 'white' // a non-ajax option
      },
      eventRender: function(event, element) {
        var statusMapping = {
          canceled: 'graffit',
          confirmed: 'darkgreen',
          pending: 'grape',
          reserved: 'darkblue'
        }
        element.addClass(statusMapping[event.status]);
      }
    });

  });
EOT;

        wp_add_inline_script( 'ea-full-calendar', $script);

        return "<div id='ea-full-calendar-{$id}'></div>";
    }

    /**
     * @param $array
     * @return string
     */
    protected function convert_csv_to_js_array_of_strings($arrayString)
    {
        $raw_array = explode(',', $arrayString);

        $wrapped_array = array_map(function($element) {
            return "'{$element}'";
        }, $raw_array);

        return '[' . implode(',', $wrapped_array) . ']';
    }
}