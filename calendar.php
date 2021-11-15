    <div id="calendar">

        <?php
        // Dates
        $year = date('Y');
        $first_month_this_year_ts = mktime(0, 0, 0, date("F", strtotime('first month of this year')) + 1, date('d'), date('Y')); // timestamp
        $today = date("j F Y", strtotime('today')); // human date
        $today_ts = strtotime('today'); // timestamp
        $today_day_of_year = date("z", $today_ts);// int

        // Calculate number of weeks in a year
        function num_weeks_in_year($year) {
            $daySum = 0;
            for ($x = 1; $x <= 12; $x++)
                $daySum += cal_days_in_month(CAL_GREGORIAN, $x, $year);
            return $daySum / 7; // int
        }
        $num_weeks_in_year_int = substr(num_weeks_in_year($year), 0, 2); // 52
        $current_week_n = idate('W', mktime(0, 0, 0, date('m'), date('d'), date('Y'))); // 28

        // Returns the "adjusted" number of weeks in a month, for the weekly view (some months will have 5 weeks since months dont always start on a Monday)
        function total_weeks_in_month($year, $month, $start_day_of_week)  {
            // Total number of days in the given month.
            $num_of_days = date("t", mktime(0,0,0,$month,1,$year));
            // Count the number of times it hits $start_day_of_week.
            $num_of_weeks = 0;
            for($i=1; $i<=$num_of_days; $i++) {
                $day_of_week = date('w', mktime(0,0,0,$month,$i,$year));
                if($day_of_week==$start_day_of_week)
                    $num_of_weeks++;
            }
            return $num_of_weeks;
        }
        ?>

        <div class="calendar">
            <div class="calendarMonthWrap">

                <!-- Months -->
                <div class="calendarMonth">
                    <?php
                    // Get all months of the year
                    $first_month_this_year_monthloop_ts = $first_month_this_year_ts;
                    for ($m = 1; $m <= 12; $m++) {
                        // How many "adjusted" weeks this months? end and beginning of 2 months could be in the same week view
                        $total_weeks = total_weeks_in_month($year, $m,0);
                        $month = strftime('%B', $first_month_this_year_monthloop_ts);
                        $first_month_this_year_monthloop_ts = strtotime('+1 month', $first_month_this_year_monthloop_ts);
                        // Current Month Class
                        if ($m == 0) {
                            $month_class = 'first';
                        } elseif ($m == 11) {
                            $month_class = 'last';
                        } elseif ($month == date("F", strtotime('today'))) {
                            $month_class = 'active-month';
                        } else {
                            $month_class = '';
                        }
                        echo '<div class="month ' . $month_class . '">' . date_i18n("F", strtotime($month)) . ' <strong>' . date('Y') . '</strong>';
                        ?>

                        <!-- Weeks -->
                        <div class="weekWrap">
                            <div class="calendarWeek">
                                <?php
                                // Get all weeks of the year
                                $first_day_of_month = new DateTime('first day of ' . date("F", strtotime($month)) . ' ' . date('Y'));
                                // Since the first day may not be a Monday, let's find the monday right before the first month day
                                $first_day_of_month_ts = strtotime($first_day_of_month->format('j F Y'));

                                for ($w = 0; $w < $total_weeks; $w++) {
                                    
                                    // We need this value to update as we loop
                                   if($w != 0 ){
                                        $first_day_of_month_ts = strtotime('+1 week', $first_day_of_month_ts);
                                    } else {
                                        $first_day_of_month_ts = strtotime('+0  week', $first_day_of_month_ts);
                                   }
                                    // Week div classes
                                    $week_of_day_class = '';
                                    $week_active = '';

                                    if ($w == 0) {
                                        $week_of_day_class = ' first_week_of_month ';
                                        $week_active = '';
                                    } elseif (($w + 1) == $total_weeks) {
                                        $week_of_day_class = ' last_week_of_month ';
                                        $week_active = '';
                                    }

                                    if (($w + 1) == ceil( date( 'j', strtotime( 'today' ) ) / 7 ) && date("F", strtotime('today')) == $month) {
                                        $week_active = ' weeksActive';
                                    } elseif ($w == 0 && date("F", strtotime('today')) != $month) {
                                    }

                                    // Start Week block
                                    echo '<div class="weeks w' . ($w + 1) . $week_of_day_class . $week_active . '">';
                                    echo '<table border="0" cellpadding="5" cellspacing="0">';

                                    // Get week days first letter
                                    echo '<tr id="week" class="day">';
                                    for ($d = 0; $d < 7; $d++) {
                                        $day_long_ts = strtotime('monday +' . $d . ' day this week ' . date('Y-m-d', $first_day_of_month_ts));
                                        $day_long = date_i18n("D", $day_long_ts);
                                        $day = $day_long[0];
                                        // Current Day Class
                                        $day_of_the_year = date("z", $day_long_ts);
                                        if ($today_day_of_year == $day_of_the_year) {
                                            $current_class = ' current';
                                        } else {
                                            $current_class = '';
                                        }
                                        echo '<td class="d' . $day_of_the_year . $current_class . '">' . $day . '</td>';
                                    }
                                    echo '</tr>';

                                    // Get week day number
                                    echo '<tr id="day" class="day">';
                                    for ($d = 0; $d < 7; $d++) {
                                        $dayn_ts = strtotime('monday +' . $d . ' day this week ' . date('Y-m-d', $first_day_of_month_ts));
                                        $dayn = date_i18n("j", $dayn_ts);
                                        // Current Day N Class
                                        $day_of_the_year = date("z", $dayn_ts);
                                        if ($today_day_of_year == $day_of_the_year) {
                                            $current_class = ' current';
                                        } else {
                                            $current_class = '';
                                        }
                                        echo '<td class="d' . $day_of_the_year . $current_class . '">' . $dayn . '</td>';
                                    }
                                    echo '</tr>';

                                    // Get calendar Events
                                    echo '<tr class="C-event">';
                                    for ($d = 0; $d < 7; $d++) {
                                        $weekday_ts = strtotime('monday +' . $d . ' day this week ' . date('Y-m-d', $first_day_of_month_ts));
                                        $day_of_the_year = date("z", $weekday_ts);
                                        $beginning_of_day = strtotime("midnight", $weekday_ts);
                                        $end_of_day = strtotime("tomorrow", $beginning_of_day) - 1;
                                        
                                        // Check if values are cached, if not cache them
                                        $events_today = 'custom_weekly_cal_' . date('j', $weekday_ts) . date('F', $weekday_ts) . date('Y', $weekday_ts) . '_6h';
                                        //delete_transient($events_today);
                                        if (get_transient($events_today) === false) {
                                            // Get posts for each day
                                            $loop_news = new WP_Query(array(
                                                'posts_per_page' => 1,
                                                'post_type' => 'post',
                                                'meta_query' => array(
                                                    array(
                                                        'key' => 'wpcf-data_inizio', // custom field with event start timestamp
                                                        'value' => array($beginning_of_day, $end_of_day),
                                                        'compare' => 'BETWEEN',
                                                        'type' => 'numeric'
                                                    ),
                                                )
                                            ));
                                            // Get events for each day
                                            $loop_events = new WP_Query(array(
                                                'posts_per_page' => 1,
                                                'post_type' => 'evento',
                                                'meta_query' => array(
                                                    array(
                                                        'key' => 'wpcf-data_inizio',
                                                        'value' => array($beginning_of_day, $end_of_day),
                                                        'compare' => 'BETWEEN',
                                                        'type' => 'numeric'
                                                    ),
                                                )
                                            ));
                                            // Get corsi for each day
                                            $loop_corsi = new WP_Query(array(
                                                'posts_per_page' => 1,
                                                'post_type' => 'corso',
                                                'meta_query' => array(
                                                    array(
                                                        'key' => 'wpcf-data_inizio',
                                                        'value' => array($beginning_of_day, $end_of_day),
                                                        'compare' => 'BETWEEN',
                                                        'type' => 'numeric'
                                                    ),
                                                )
                                            ));
                                            
                                            // Final Query
                                            $final_query = new WP_Query();
                                            // Merging queries
                                            $final_query->posts = array_merge($loop_news->posts, $loop_events->posts, $loop_corsi->posts);
                                            // Recount
                                            $final_query->post_count = count($final_query->posts);
                                            // Cache Results
                                            set_transient($events_today, $final_query, 6 * HOUR_IN_SECONDS);
                                        }
                                        $final_query = get_transient($events_today);
                                        $post_count = $final_query->post_count;

                                        // Convert number into letters
                                        if ($post_count > 0) {
                                            $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
                                            $num_events = $f->format($post_count) . 'Event';
                                        } else {
                                            $num_events = '';
                                        }
                                        // Current Event Class
                                        if ($today_ts == $weekday_ts) {
                                            $current_class = ' current';
                                        } else {
                                            $current_class = '';
                                        }
                                        
                                        // Event dots wrapper
                                        echo '<td class="' . $num_events . ' d' . $day_of_the_year . ' ' . $current_class . '">';
                                        if ($post_count > 0) {
                                            if ($final_query->have_posts()) :
                                                while ($final_query->have_posts()) : $final_query->the_post();
                                                    $post_type = get_post_type();
                                                    // Colore
                                                    if ($post_type == 'post') {
                                                        $post_color_class = CLASS_CALENDAR_POST;
                                                        $post_data_class = DATA_CALENDAR_POST;
                                                    } elseif ($post_type == 'evento') {
                                                        $post_color_class = CLASS_CALENDAR_EVENTO;
                                                        $post_data_class = DATA_CALENDAR_EVENTO;
                                                    } elseif ($post_type == 'corso') {
                                                        $post_color_class = CLASS_CALENDAR_CORSO;
                                                        $post_data_class = DATA_CALENDAR_CORSO;
                                                    } elseif ($post_type == 'prenotazione') {
                                                        $post_color_class = CLASS_CALENDAR_PRENOTAZIONE;
                                                        $post_data_class = DATA_CALENDAR_PRENOTAZIONE;
                                                    } else {
                                                        $post_color_class = '';
                                                        $post_data_class = '';
                                                    }
                                                    echo '<span data-Name="' . get_the_title() . '" data-eventcolor="' . $post_data_class . '" class="eventCricle ' . $post_color_class . '"></span>';
                                                endwhile;
                                            endif; wp_reset_query(); wp_reset_postdata();
                                        }
                                        echo '</td>';
                                    } // end of daily Events
                                    echo '</tr>';
                                    ?>

                                    <?php
                                    // End of Week Block
                                    echo '</table></div>';
                                }
                                ?>

                            </div>

                        </div>

                        <?php
                        echo '</div>';
                    }
                    ?>
                </div>
                <!-- Calendar Navigation -->
                <div class="prev month-nav"><i class="fa fa-angle-double-left" aria-hidden="true"></i></div>
                <div class="next month-nav"><i class="fa fa-angle-double-right" aria-hidden="true"></i></div>
                <div class="prev week-nav"><i class="fa fa-angle-double-left" aria-hidden="true"></i></div>
                <div class="next week-nav"><i class="fa fa-angle-double-right" aria-hidden="true"></i></div>

            </div>
        </div>
        <!-- Title of events / placeholder -->
        <div class="bottomtext">
            <?php
            // Placeholder Events for today
            $placeholder_query = get_transient('custom_weekly_cal_' . date('j') . date('F') . date('Y') . '_6h');
            if ($placeholder_query) {
                if ($placeholder_query->have_posts()) : while ($placeholder_query->have_posts()) : $placeholder_query->the_post();
                        the_title();
                    endwhile;
                endif; wp_reset_query(); wp_reset_postdata();
            }
            ?>
        </div>
        
    </div>
