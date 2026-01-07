<?php
global $rea;
$players = $data['allMatchStatsSummary']['lineUp']['players'];
usort($players, function ($a, $b) {
  return $a['position'] <=> $b['position']; // ascending
});
$fixture_item = $data['getFixtureItem'];
$is_home = false;

if (!empty($fixture_item)) {
  $is_home = in_array($fixture_item['homeTeam']['teamId'], $rea->rugbyexplorer->get_team_ids());
}
error_log(print_r($data['getFixtureItem'], true));
error_log(print_r($is_home, true));

error_log(print_r($rea->rugbyexplorer->get_team_ids(), true));



?>
<div class="elementor-widget-heading">
  <div class="container" style="display:flex; margin-bottom: 20px; justify-content: center;">
    <div style="display: flex; flex-direction: column; align-items: center;">
      <?php if ($is_home) { ?>
        <img width="100" height="100" src="<?php echo $data['getFixtureItem']['homeTeam']['crest']; ?>" alt="<?php echo $data['getFixtureItem']['homeTeam']['name']; ?>">
        <b><?php echo $data['getFixtureItem']['homeTeam']['name']; ?></b>
      <?php } else {  ?>
        <img width="100" height="100" src="<?php echo $data['getFixtureItem']['awayTeam']['crest']; ?>" alt="<?php echo $data['getFixtureItem']['awayTeam']['name']; ?>">
        <b><?php echo $data['getFixtureItem']['awayTeam']['name']; ?></b>
      <?php } ?>
    </div>
  </div>

  <div class="container" style="display:flex; justify-content: center;">
    <div>
      <?php
      foreach ($players as $player) {
        if ($player['isHome'] == $is_home) { ?>
          <div style="padding: 10px; display: flex; align-items: center; ">
            <span style="font-weight: 600; font-size: 1.25rem"><?php echo $player['position']; ?></span>
            <span style="margin-left: 20px;"><?php echo $player['name']; ?><?php echo $player['captainType'] == "captain" ? " (C)" : ""; ?></span>
          </div>
      <?php
        }
      } ?>
    </div>
  </div>
  <h2 class="elementor-heading-title elementor-size-default" style="text-align: center; font-size: 22px; margin-top: 20px;">Substitutes</h2>
  <div class="container" style="display:flex; justify-content: center;">
    <div>
      <?php
      foreach ($data['allMatchStatsSummary']['lineUp']['substitutes'] as $sub) {
        if ($sub['isHome'] == $is_home) { ?>
          <div style="padding: 10px; display: flex; align-items: center; justify-content: flex-end; ">
            <span style="font-weight: 600; font-size: 1.25rem"><?php echo $sub['position']; ?></span>
            <span style="margin-left: 20px;"><?php echo $sub['name']; ?></span>
          </div>
      <?php
        }
      } ?>
    </div>
  </div>
  <h2 class="elementor-heading-title elementor-size-default" style="text-align: center; font-size: 22px; margin-top: 20px;">Coaches</h2>
  <div class="container" style="display:flex; justify-content: center;">
    <div>
      <?php
      foreach ($data['allMatchStatsSummary']['lineUp']['coaches'] as $coach) {
        if ($coach['isHome'] == $is_home) { ?>
          <div style="padding: 10px; display: flex; align-items: center; justify-content: flex-end; ">
            <span><?php echo $coach['name']; ?></span>
          </div>
      <?php
        }
      } ?>
    </div>
  </div>
  <h2 class="elementor-heading-title elementor-size-default" style="text-align: center; font-size: 22px; margin-top: 20px;">Referees</h2>
  <div class="container" style="display:flex; gap: 10px; flex-direction: column; align-items: center;">
    <?php
    foreach ($data['allMatchStatsSummary']['referees'] as $ref) { ?>
      <div style="padding: 10px; display: flex; align-items: center; justify-content: flex-end; ">
        <span style="margin-right: 20px; font-weight: 600;"><?php echo $ref['type']; ?></span>
        <span><?php echo $ref['refereeName']; ?></span>
      </div>
    <?php
    } ?>
  </div>
</div>