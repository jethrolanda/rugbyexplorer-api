<?php
$tries = $data['allMatchStatsSummary']['pointsSummary']['tries'];
$conversions = $data['allMatchStatsSummary']['pointsSummary']['conversions'];
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
<?php if (!empty($tries)) { ?>
  <div style="display: flex;">
    <div style="flex:1; text-align: right;">
      <div style="display: flex; flex-direction: column;">
        <?php
        foreach ($tries as $trie) {
          if ($trie['isHome']) {
        ?>
            <div>
              <span><?php echo $trie['playerName']; ?></span> <?php echo $trie['pointsMinute']; ?>'
            </div>
        <?php
          }
        }
        ?>
      </div>
    </div>
    <div style="flex:1; text-align: center; max-width: 150px;"><b>Tries</b></div>
    <div style="flex:1; text-align: left;">
      <div style="display: flex; flex-direction: column;">
        <?php
        foreach ($tries as $trie) {
          if (!$trie['isHome']) {
        ?>
            <div>
              <span><?php echo $trie['playerName']; ?></span> <?php echo $trie['pointsMinute']; ?>'
            </div>
        <?php
          }
        }
        ?>
      </div>
    </div>
  </div>
<?php } ?>
<br>
<?php if (!empty($conversion)) { ?>
  <div style="display: flex;">
    <div style="flex:1; text-align: right;">
      <div style="display: flex; flex-direction: column;">
        <?php
        foreach ($conversions as $conversion) {
          if ($conversion['isHome']) {
        ?>
            <div>
              <span><?php echo $conversion['playerName']; ?></span> <?php echo $conversion['pointsMinute']; ?>'
            </div>
        <?php
          }
        }
        ?>
      </div>
    </div>
    <div style="flex:1; text-align: center; max-width: 150px;"><b>Conversions</b></div>
    <div style="flex:1; text-align: left;">
      <div style="display: flex; flex-direction: column;">
        <?php
        foreach ($conversions as $conversion) {
          if (!$conversion['isHome']) {
        ?>
            <div>
              <span><?php echo $conversion['playerName']; ?></span> <?php echo $conversion['pointsMinute']; ?>'
            </div>
        <?php
          }
        }
        ?>

      </div>
    </div>
  </div>
<?php } ?>