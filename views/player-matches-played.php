<?php



if (! defined('ABSPATH')) {
  exit; // Exit if accessed directly
}

$defaults = array(
  'id'                   => null,
  'event'                => null,
  'title'                => false,
  'status'               => 'default',
  'format'               => 'default',
  'date'                 => 'default',
  'date_from'            => 'default',
  'date_to'              => 'default',
  'date_past'            => 'default',
  'date_future'          => 'default',
  'date_relative'        => 'default',
  'day'                  => 'default',
  'league'               => null,
  'season'               => null,
  'venue'                => null,
  'team'                 => null,
  'teams_past'           => null,
  'date_before'          => null,
  'player'               => null,
  'number'               => -1,
  'show_team_logo'       => get_option('sportspress_event_blocks_show_logos', 'yes') == 'yes' ? true : false,
  'link_teams'           => get_option('sportspress_link_teams', 'no') == 'yes' ? true : false,
  'link_events'          => get_option('sportspress_link_events', 'yes') == 'yes' ? true : false,
  'paginated'            => get_option('sportspress_event_blocks_paginated', 'yes') == 'yes' ? true : false,
  'rows'                 => get_option('sportspress_event_blocks_rows', 5),
  'orderby'              => 'default',
  'order'                => 'default',
  'columns'              => null,
  'show_all_events_link' => false,
  'show_title'           => get_option('sportspress_event_blocks_show_title', 'no') == 'yes' ? true : false,
  'show_league'          => get_option('sportspress_event_blocks_show_league', 'no') == 'yes' ? true : false,
  'show_season'          => get_option('sportspress_event_blocks_show_season', 'no') == 'yes' ? true : false,
  'show_matchday'        => get_option('sportspress_event_blocks_show_matchday', 'no') == 'yes' ? true : false,
  'show_venue'           => get_option('sportspress_event_blocks_show_venue', 'no') == 'yes' ? true : false,
  'hide_if_empty'        => false,
);

extract($defaults, EXTR_SKIP);
?>
<div class="sp-template sp-template-event-blocks">
  <div class="sp-table-wrapper">
    <table class="sp-event-blocks sp-data-table">
      <tbody>
        <?php
        foreach ($games_played as $event_id) {
          $event = get_post($event_id);
          $permalink = get_post_permalink($event, false, true);
          $results   = sp_get_main_results_or_time($event);

          $teams        = array_unique(get_post_meta($event->ID, 'sp_team'));
          $teams        = array_filter($teams, 'sp_filter_positive');
          $logos        = array();
          $event_status = get_post_meta($event->ID, 'sp_status', true);

          if (get_option('sportspress_event_reverse_teams', 'no') === 'yes') {
            $teams   = array_reverse($teams, true);
            $results = array_reverse($results, true);
          }

          if ($show_team_logo) :
            $j = 0;
            foreach ($teams as $team) :
              $j++;

              $team_name = get_the_title($team);

              // Use short name if set, otherwise full title
              $display_name = get_post_meta($team, 'sp_short_name', true);
              if (! $display_name) {
                $display_name = $team_name;
              }

              // Build logo (image / link)
              $logo_img = '';
              if (has_post_thumbnail($team)) :
                $logo_img = get_the_post_thumbnail($team, 'sportspress-fit-icon', array('itemprop' => 'logo'));

                if ($link_teams) :
                  $team_permalink = get_permalink($team, false, true);
                  $logo_img       = '<a href="' . esc_url($team_permalink) . '" itemprop="url" content="' . esc_url($team_permalink) . '">' . $logo_img . '</a>';
                endif;
              endif;

              // Wrap logo + name together so the name sits under the logo
              $logo  = '<span class="team-logo logo-' . ($j % 2 ? 'odd' : 'even') . '"';
              $logo .= ' title="' . esc_attr($team_name) . '" itemprop="competitor" itemscope itemtype="http://schema.org/SportsTeam">';
              $logo .= '<meta itemprop="name" content="' . esc_attr($team_name) . '">';
              if ($logo_img) {
                $logo .= '<span class="sp-team-logo-image">' . $logo_img . '</span>';
              }
              $logo .= '<span class="sp-team-name">' . esc_html($display_name) . '</span>';
              $logo .= '</span>';

              $logos[] = $logo;
            endforeach;
          endif;

        ?>
          <tr class="sp-row" itemscope itemtype="http://schema.org/SportsEvent">
            <td>

              <?php echo wp_kses_post(implode(' ', $logos)); ?>
              <time class="sp-event-date" datetime="<?php echo esc_attr($event->post_date); ?>" itemprop="startDate" content="<?php echo esc_attr(mysql2date('Y-m-d\TH:i:sP', $event->post_date)); ?>">
                <?php echo wp_kses_post(sp_add_link(get_the_time(get_option('date_format'), $event), $permalink, $link_events)); ?>
              </time>
              <?php
              if ($show_matchday) :
                $matchday = get_post_meta($event->ID, 'sp_day', true);
                if ($matchday != '') :
              ?>
                  <div class="sp-event-matchday">(<?php echo wp_kses_post($matchday); ?>)</div>
              <?php
                endif;
              endif;
              ?>
              <h5 class="sp-event-results">
                <?php echo wp_kses_post(sp_add_link('<span class="sp-result ' . $event_status . '">' . implode('</span> - <span class="sp-result">', apply_filters('sportspress_event_blocks_team_result_or_time', $results, $event->ID)) . '</span>', $permalink, $link_events)); ?>
              </h5>
              <?php
              if ($show_league) :
                $leagues = get_the_terms($event, 'sp_league');
                if ($leagues) :
                  $league = array_shift($leagues);
              ?>
                  <div class="sp-event-league"><?php echo wp_kses_post($league->name); ?></div>
              <?php
                endif;
              endif;
              ?>
              <?php
              if ($show_season) :
                $seasons = get_the_terms($event, 'sp_season');
                if ($seasons) :
                  $season = array_shift($seasons);
              ?>
                  <div class="sp-event-season"><?php echo wp_kses_post($season->name); ?></div>
              <?php
                endif;
              endif;
              ?>
              <?php
              if ($show_venue) :
                $venues = get_the_terms($event, 'sp_venue');
                if ($venues) :
                  $venue = array_shift($venues);
              ?>
                  <div class="sp-event-venue" itemprop="location" itemscope itemtype="http://schema.org/Place">
                    <div itemprop="address" itemscope itemtype="http://schema.org/PostalAddress"><?php echo wp_kses_post($venue->name); ?></div>
                  </div>
              <?php
                endif;
              endif;
              ?>
              <?php if (! $show_venue || ! $venues) : ?>
                <div style="display:none;" class="sp-event-venue" itemprop="location" itemscope itemtype="http://schema.org/Place">
                  <div itemprop="address" itemscope itemtype="http://schema.org/PostalAddress"><?php esc_attr_e('N/A', 'sportspress'); ?></div>
                </div>
              <?php endif; ?>
              <h4 class="sp-event-title" itemprop="name">
                <?php
                $game_data = get_post_meta($event->ID, 'rugby_explorer_game_data', true);
                echo $game_data['roundLabel'];
                ?>
              </h4>

            </td>
          </tr>
        <?php
        } ?>
      </tbody>
    </table>
  </div>
</div>