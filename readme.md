## Introduction

Import data from https://xplorer.rugby/ into SportsPress plugin via API.
Creates: Events, Venue, Teams, Leagues, Seasons, Venues, Teams, Players, Staffs, Jobs, Officials, Duties

## Sortcodes

[player_lineup]

- Displays the team starting lineup, substitutes, coaches and referees for both teams.
- The data is fetched from RugbyExplorer not from SportsPress.
- Add this shortcode in the event page post content.
- No attributes needed.
- This shortcode is using rugbyexplorer event match id. The match id is added in the event post meta where the name is “fixture_id” after import.

[points_summary]

- Displays the points summary data of the match.
- The data is fetched from RugbyExplorer not from SportsPress.
- Add this shortcode in the event page post content.
- No attributes needed.
- This shortcode is using rugbyexplorer event match id. The match id is added in the event post meta where the name is “fixture_id” after import.

[team_ladder competition_id="mLGoqgHnacX2AnmgD"]

- Displays the competition ladder.
- The data is fetched from RugbyExplorer not from SportsPress.
- Can be added anywhere post or pages.
- Required ‘competition_id’ attribute.
- The competition_id can be grabbed from the rugbyexplorer url. Example: https://xplorer.rugby/jruc/fixtures-results?team=DZJhdynaY4wSDBQpQ&comp=mLGoqgHnacX2AnmgD&season=2025&tab=Ladder

[team_events entity_id="53371" competition_id="mLGoqgHnacX2AnmgD" season="2025" team_id="DZJhdynaY4wSDBQpQ"]

- Displays the competition matches
- The data is fetched from RugbyExplorer not from SportsPress.
- Requires the following attributes: entity_id, competition_id, season and team_id
- To find the entity_id, visit the entity url page (Example: https://xplorer.rugby/jruc) then page source then find “entityId” there you will find the id.
- To find competition_id, season and team_id just grab it from the rugbyexplorer url. Example: https://xplorer.rugby/jruc/fixtures-results?team=DZJhdynaY4wSDBQpQ&comp=mLGoqgHnacX2AnmgD&season=2025&tab=Results

[top_scorer playerlist_id="57268" season="2024"]

- I am copying the playerlist shortcode template (player-list.php) so that I can override the team id based on the current team page.
- Requires playerlist_id to work properly.
- Season attribute is optional. If no season then it will rely on the playerlist season setting.
- Goto https://staging.focosme.com/wp-admin/edit.php?post_type=sp_list and create a top scorer for Try and Conversion. Just leave the team blank.
- It will use the settings from the player list post id
- If adde in the team template, this shortcode will just automatically detect the current team page id and autofilter the players based on the current team page.

---

## Blocks

None

## Zip File

To create a .zip file of this plugin. First clone this repository and create a zip using this code:
`git archive -o rugbyexplorer-api.zip HEAD`
