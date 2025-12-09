<?php
$players = $data['allMatchStatsSummary']['lineUp']['players'];
usort($players, function ($a, $b) {
  return $a['position'] <=> $b['position']; // ascending
});
?>
<div class="elementor-widget-heading">
  <div class="container" style="display:flex; gap: 50px; margin-bottom: 20px;">
    <div class="home" style="flex: 1;">
      <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 10px;">
        <img width="100" height="100" src="<?php echo $data['getFixtureItem']['homeTeam']['crest']; ?>" alt="<?php echo $data['getFixtureItem']['homeTeam']['name']; ?>">
        <b><?php echo $data['getFixtureItem']['homeTeam']['name']; ?></b>
      </div>
    </div>
    <div class="away" style="flex: 1;">
      <div style="display: flex; flex-direction: column; gap: 10px;">
        <img width="100" height="100" src="<?php echo $data['getFixtureItem']['awayTeam']['crest']; ?>" alt="<?php echo $data['getFixtureItem']['awayTeam']['name']; ?>">
        <b><?php echo $data['getFixtureItem']['awayTeam']['name']; ?></b>
      </div>
    </div>
  </div>

  <div class="container" style="display:flex; gap: 10px;">
    <div class="home" style="flex: 1;">
      <?php
      foreach ($players as $player) {
        if ($player['isHome']) { ?>
          <div style="padding: 10px; display: flex; align-items: center; justify-content: flex-end; ">
            <span><?php echo $player['name']; ?><?php echo $player['captainType'] == "captain" ? " (C)" : ""; ?></span>
            <span style="margin-left: 20px; font-weight: 600; font-size: 1.25rem"><?php echo $player['position']; ?></span>
          </div>
      <?php
        }
      } ?>
    </div>
    <div class="away" style="flex: 1;">
      <?php
      foreach ($players as $player) {
        if (!$player['isHome']) { ?>
          <div style="padding: 10px; display: flex; align-items: center;">
            <span style="margin-right: 20px; font-weight: 600; font-size: 1.25rem"><?php echo $player['position']; ?></span>
            <span><?php echo $player['captainType'] == "captain" ? "(C) " : ""; ?><?php echo $player['name']; ?></span>
          </div>
      <?php
        }
      } ?>
    </div>
  </div>
  <h2 class="elementor-heading-title elementor-size-default" style="text-align: center; font-size: 22px; margin-top: 20px;">Substitutes</h2>
  <div class="container" style="display:flex; gap: 10px;">
    <div class="home" style="flex: 1;">
      <?php
      foreach ($data['allMatchStatsSummary']['lineUp']['substitutes'] as $sub) {
        if ($sub['isHome']) { ?>
          <div style="padding: 10px; display: flex; align-items: center; justify-content: flex-end; ">
            <span><?php echo $sub['name']; ?></span>
            <span style="margin-left: 20px; font-weight: 600; font-size: 1.25rem"><?php echo $sub['position']; ?></span>
          </div>
      <?php
        }
      } ?>
    </div>
    <div class="away" style="flex: 1;">
      <?php
      foreach ($data['allMatchStatsSummary']['lineUp']['substitutes'] as $sub) {
        if (!$sub['isHome']) { ?>
          <div style="padding: 10px; display: flex; align-items: center;">
            <span style="margin-right: 20px; font-weight: 600; font-size: 1.25rem"><?php echo $sub['position']; ?></span>
            <span><?php echo $sub['name']; ?></span>
          </div>
      <?php
        }
      } ?>
    </div>
  </div>
  <h2 class="elementor-heading-title elementor-size-default" style="text-align: center; font-size: 22px; margin-top: 20px;">Coaches</h2>
  <div class="container" style="display:flex; gap: 10px;">
    <div class="home" style="flex: 1;">
      <?php
      foreach ($data['allMatchStatsSummary']['lineUp']['coaches'] as $coach) {
        if ($coach['isHome']) { ?>
          <div style="padding: 10px; display: flex; align-items: center; justify-content: flex-end; ">
            <span><?php echo $coach['name']; ?></span>
          </div>
      <?php
        }
      } ?>
    </div>
    <div class="away" style="flex: 1;">
      <?php
      foreach ($data['allMatchStatsSummary']['lineUp']['coaches'] as $coach) {
        if (!$coach['isHome']) { ?>
          <div style="padding: 10px; display: flex; align-items: center;">
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