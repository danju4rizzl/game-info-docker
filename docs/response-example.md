List lives matches
get
https://api.pandascore.co/lives
List currently running live matches, available from pandascore with live websocket data.

ℹ️
This endpoint is available to all customers

Query Params
page
Pagination in the form of page=2 or page[size]=30&page[number]=2

integer

object
per_page
integer
1 to 100
Defaults to 50
Equivalent to page[size]

5
Responses

200
A list of games being played or about to be played

Response body
array of objects
object
endpoints
array of objects
required
object
begin_at
date-time | null
required
expected_begin_at
date-time | null
required
last_active
integer | null
required
≥ 0
Timestamp in milliseconds (since January 1, 1970 00:00:00 UTC)

match_id
integer
required
≥ 1
open
boolean
required
Whether live is open

type
string
required
events frames

url
uri
required
match
object
required

Match object
begin_at
date-time | null
required
detailed_stats
boolean
required
Whether the match offers full stats

draw
boolean
required
Whether result of the match is a draw

end_at
date-time | null
required
forfeit
boolean
required
Whether match was forfeited

game_advantage
integer | null
required
≥ 1
ID of the opponent with a game advantage

games
array of objects
required
object
begin_at
date-time | null
required
The game begin time, UTC.
null when the game status is not_started

complete
boolean
required
Whether When true, the game statistics are complete and will not be updated again

detailed_stats
boolean
required
Whether historical data is available for the game

end_at
date-time | null
required
The game end time, UTC.
null when the game status is not finished

finished
boolean
required
Whether the game is finished

forfeit
boolean
required
Whether the game has been forfeited

id
required
ID of the game.
IDs are video game-specific, ie. a Valorant game and an Overwatch game can have the same game ID.

LoLGameID

CSGOGameID
Counter-Strike game ID

integer

OwGameID

Dota2GameID

PUBGGameID

ValorantGameID
length
integer | null
required
≥ 0
Duration of the game in seconds.
null when the game status is not finished

match_id
integer
required
≥ 1
position
integer
required
≥ 1
Game position in the match. Starts at 1

status
string
required
The game status

finished not_played not_started running

winner
object
required

GameWinner object
id
required

PlayerID

TeamID
type
string | null
required
Player Team

winner_type
string | null
required
Player Team

id
integer
required
≥ 1
league
object
required

BaseLeague object
id
integer
required
≥ 1
image_url
uri | null
required
modified_at
date-time
required
length ≥ 1
name
string
required
slug
string
required
length ≥ 1
url
uri | null
required
league_id
integer
required
≥ 1
live
object
required

MatchLive object
opens_at
date-time | null
required
supported
boolean
required
Whether live is supported

url
uri | null
required
map_picks
array of objects | null
Only applies to Valorant matches. The field will not be present on other video games matches.
Map picks, null when map picks data is unavailable.
Important: map_picks field is only present in the response for subscribers of Valorant Historical plan.

object
id
integer
required
≥ 1
ID of the map

image_url
uri
required
URL to an image of the map

name
string
required
Name of the map

picking_team_id
integer | null
required
≥ 1
ID of the team that picked the map

slug
string
required
length ≥ 1
Human-readable identifier of the map

videogame_versions
array of strings
required
Array of of video game versions (ie. patches) for this resource

match_type
string
required
all_games_played best_of custom first_to ow_best_of red_bull_home_ground

modified_at
date-time
required
length ≥ 1
name
string
required
number_of_games
integer
required
≥ 0
Number of games

opponents
array of objects
required
object
opponent
required

BasePlayer

BaseTeam
object
acronym
string | null
required
id
integer
required
≥ 1
The ID of the team.

image_url
uri | null
required
URL of the team logo

location
string | null
required
The team's organization location

modified_at
date-time
required
length ≥ 1
name
string
required
The name of the team.

slug
string | null
required
type
string
required
Player Team

original_scheduled_at
date-time | null
required
rescheduled
boolean | null
required
Whether match has been rescheduled

results
array
required

MatchTeamResult
object
score
integer
required
≥ 0
team_id
integer
required
≥ 1
The ID of the team.

MatchPlayerResult
scheduled_at
date-time | null
required
serie
object
required

BaseSerie object
serie_id
integer
required
≥ 1
slug
string | null
required
status
string
required
canceled finished not_started postponed running

streams_list
array of objects
required
object
embed_url
uri | null
required
URL to embed in an iframe.

language
string
required
Language alpha-2 code according to ISO 649-1 standard.

aa ab ae af ak am an ar as av ay az ba be bg bh bi bm bn bo br bs ca ce ch co cr cs cu cv cy da de dv dz ee el en eo es et eu fa ff fi fj fo fr fy ga gd gl gn gu gv ha he hi ho hr ht hu hy hz ia id ie ig ii ik io is it iu ja jv ka kg ki kj kk kl km kn ko kr ks ku kv kw ky la lb lg li ln lo lt lu lv mg mh mi mk ml mn mr ms mt my na nb nd ne ng nl nn no nr nv ny oc oj om or os pa pi pl ps pt qu rm rn ro ru rw sa sc sd se sg si sk sl sm sn so sq sr ss st su sv sw ta te tg th ti tk tl tn to tr ts tt tw ty ug uk ur uz ve vi vo wa wo xh yi yo za zh zu

main
boolean
required
Whether it is the main stream. Main stream is always official.

official
boolean
required
Whether it is an official broadcast.

raw_url
uri
required
URL to the stream on host website.

tournament
object
required

BaseTournament object
begin_at
date-time | null
required
country
string | null
required
Country code matching the location of the tournament according to the ISO 3166-1 standard (Alpha-2 code). In addition to the standard, the XK code is used for Kosovo. null if unknown

detailed_stats
boolean
required
Whether the tournament is expected to have detailed statistics available

end_at
date-time | null
required
has_bracket
boolean
required
Whether the tournament has a bracket

id
integer
required
≥ 1
league_id
integer
required
≥ 1
live_supported
boolean
required
Whether live is supported

modified_at
date-time
required
length ≥ 1
name
string
required
prizepool
string | null
required
region
string | null
required
Region acronym for the location of the tournament.

ASIA EEU ME NA OCE SA WEU

serie_id
integer
required
≥ 1
slug
string
required
length ≥ 1
tier
string | null
required
The tier of the tournament, ranging from 'S' to 'Unranked'. Ranking 'S' > 'A' > 'B' > 'C' > 'D' > 'Unranked'

a b c d s unranked

type
string | null
required
Location type for a tournament

offline online online/offline

winner_id
required

PlayerID
ID of the player

integer

TeamID
winner_type
string | null
required
Player Team

tournament_id
integer
required
≥ 1
videogame
object
required
[object Object] [object Object] [object Object] [object Object] [object Object] [object Object] [object Object] [object Object] [object Object] [object Object] [object Object] [object Object] [object Object] [object Object] [object Object] [object Object] [object Object] [object Object]

Has additional fields
videogame_title
object | null
required

VideogameTitle object | null
id
integer
required
≥ 1
name
string
required
slug
string
required
length ≥ 1
videogame_id
integer
required
A videogame ID

1 3 4 14 20 22 23 24 25 26 27 28 29 30 31 32 33 34

videogame_version
object | null
required

ShortVideogameVersion object | null
current
boolean
required
Whether this videogame version is current

name
string
required
winner
required

BasePlayer

BaseTeam
winner_id
required

PlayerID

TeamID
winner_type
string
required
Player Team

## Request

<?php
require_once('vendor/autoload.php');

$client = new \GuzzleHttp\Client();

$response = $client->request('GET', 'https://api.pandascore.co/lives?page=1&per_page=5', [
  'headers' => [
    'accept' => 'application/json',
    'authorization' => 'Bearer xfhRcVMP3Qdf3_P4moB7V_dUtvYXqdTtTJqs400X_2f_aikUYgY',
  ],
]);

echo $response->getBody();


Events recovery
Learn how to use PandaScore WebSockets API to recover past real-time events.

Suggest Edits
📘
Notice

The events feed is available in the Live Pro Plan. For more information, see Pricing.

When sending a Recover message in the WebSocket, the server responds with the list of all events previously sent, even if the client was not connected, which will send you back all the previously pushed events of the game.

When connected to the /matches/<matchID>/events WebSocket channel, the following message should be sent to retrieve the previous events from <gameID>.

JSON

{
  "type": "recover",
  "payload": {
    "game_id": <gameID>
  }
}
Node.js example

Node.js

const socket = new WebSocket('wss://live.pandascore.co/matches/548763/events?token=YOUR_TOKEN')

socket.onmessage = function (event) {
	console.log(JSON.parse(event.data))
}

socket.onopen = function (event) {
  socket.send(JSON.stringify({"type":"recover","payload":{"game_id":211051}}))
}
