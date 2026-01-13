<?php
$tries = $data['allMatchStatsSummary']['pointsSummary']['tries'];
$conversions = $data['allMatchStatsSummary']['pointsSummary']['conversions'];
$scores = array(
  'tries' => 'Tries',
  'conversions' => 'Conversions',
  'penaltyGoals' => 'Penalty Goals',
  'fieldGoals' => 'Field Goals'
)
?>
<div style="display:flex; gap: 150px; margin-bottom: 20px;">
  <div class="home" style="flex: 1;">
    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 10px;">
      <img width="100px" height="100px" src="<?php echo $data['getFixtureItem']['homeTeam']['crest']; ?>" alt="<?php echo $data['getFixtureItem']['awayTeam']['name']; ?>">
      <b><?php echo $data['getFixtureItem']['homeTeam']['name']; ?></b>
    </div>
  </div>
  <div class="away" style="flex: 1;">
    <div style="display: flex; flex-direction: column; gap: 10px;">
      <img width="100px" height="100px" src="<?php echo $data['getFixtureItem']['awayTeam']['crest']; ?>" alt="<?php echo $data['getFixtureItem']['awayTeam']['name']; ?>">
      <b><?php echo $data['getFixtureItem']['awayTeam']['name']; ?></b>
    </div>
  </div>
</div>
<?php
foreach ($data['allMatchStatsSummary']['pointsSummary'] as $key => $score) {
  if (isset($scores[$key]) && $score) { ?>
    <div style="display: flex;">
      <div style="flex:1; text-align: right;">
        <div style="display: flex; flex-direction: column;">
          <?php
          foreach ($score as $sc) {
            if ($sc['isHome']) {
          ?>
              <div>
                <span><?php echo $sc['playerName']; ?></span> <?php echo $sc['pointsMinute']; ?>'
              </div>
          <?php
            }
          }
          ?>
        </div>
      </div>
      <div style="flex:1; text-align: center; max-width: 150px;"><b><?php echo $scores[$key]; ?></b></div>
      <div style="flex:1; text-align: left;">
        <div style="display: flex; flex-direction: column;">
          <?php
          foreach ($score as $sc) {
            if (!$sc['isHome']) {
          ?>
              <div>
                <span><?php echo $sc['playerName']; ?></span> <?php echo $sc['pointsMinute']; ?>'
              </div>
          <?php
            }
          }
          ?>
        </div>
      </div>
    </div>
    <br>
<?php
  }
}
?>