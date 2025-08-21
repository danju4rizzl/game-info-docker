Here are the pandascore api refrence page:
"List additions
get
https://api.pandascore.co/additions
Get the latest additions.

This endpoint only shows unchanged objects.

ℹ️
This endpoint is available to all customers

Recipes
Tracking changes with incidents
Open Recipe
Query Params
filter
object
Options to filter results. String fields are case sensitive
For more information on filtering, see docs.

filter object
range
object
Options to select results within ranges
For more information on ranges, see docs.

range object
sort
array
length ≥ 1
Options to sort results
For more information on sorting, see docs.

id

ADD
page
Pagination in the form of page=2 or page[size]=30&page[number]=2

integer

object
per_page
integer
1 to 100
Defaults to 50
Equivalent to page[size]

50
type
array
length ≥ 1
Filter by result type(s)

league

ADD
since
date-time
Filter out older results

videogame
array
length ≥ 1
Filter by videogame(s)

VideogameID

VideogameSlug"

### Working with live API data

Learn how to retrieve and connect to the real-time matches data feed using the WebSockets API.

Suggest Edits
This guide will help you to retrieve matches that support real-time data and connect to the dedicated WebSockets.

Connecting to WebSockets

1. Retrieving matches that support real-time data

Support for real-time data is enabled at the tournament level. When available, the tournament live_supported is true.

For all children matches in the tournament, a WebSocket is opened 15 minutes prior to the scheduled playing time.

At any time, you can use the All Video Games > List live matches to get the list of currently opened WebSockets.

This endpoint returns a list of matches. Each match has a structure similar to the example below.

JSON

{
"endpoints": [
{
"begin_at": null,
"expected_begin_at": "2021-08-13T11:34:59Z",
"last_active": null,
"match_id": 595477,
"open": true,
"type": "frames",
"url": "wss://live.pandascore.co/matches/595477"
},
{
"begin_at": null,
"expected_begin_at": "2021-08-13T11:34:59Z",
"last_active": null,
"match_id": 595477,
"open": true,
"type": "events",
"url": "wss://live.pandascore.co/matches/595477/events"
}
],
"match": {
// Omitted for clarity
}
} 2. Connecting to the real-time feeds

The previous JSON contains two endpoints: a Frames endpoint and an Events endpoint.

Using your preferred application or language, you can perform a secure connection to the WebSockets. Don't forget to include your token as a token URL parameter.

Shell
Node.js

wscat -c 'wss://live.pandascore.co/matches/595477?token=YOUR_TOKEN'
wscat is a command-line utility to connect to and display messages from WebSockets.

After a successful connection, the server sends a hello event as below.

JSON

{"type":"hello","payload":{}}
Users are allowed a maximum of 3 simultaneous connections per match per endpoint.

In case of disconnects, see Disconnections.

Frames & Events
PandaScore WebSockets API offers two real-time data feeds:

Frames — snapshot of all the game data points, taken every 2 seconds
Events — timeline of events happening in game, sent as they occur.
Frames feed
All real-time integrations start with the frames feed. As the frames data contains all team statistics, it is often crucial to have it. In fact, it makes handling the events easier.

Using the frames feed, you can create real-time scoreboard like below.

636
Example League of Legends scoreboard

For League of Legends, frames can also be retrieved after the game with the LoL > List game play-by-play frames endpoint. For Counter-Strike, rounds can be retrieved after the game with the CS > List game play-by-play rounds endpoint.

Events feed
📘
Notice

The events feed is available in the Live Pro Plan. For more information, see Pricing

On the other side, events represent a timeline of the game. Events are sent for major actions happening in game such as kills, Baron kills (in League), or the bomb exploding (in CS:GO).

Using the events feed, you can create real-time timelines like below.

635
Example Counter-Strike timeline

It is also possible to request a recap of all past events using Events recovery.

List lives matches
get
https://api.pandascore.co/lives
List currently running live matches, available from pandascore with live websocket data.

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

## Working with pagination Pagination

Learn how to paginate results from the PandaScore REST API.

Suggest Edits
PandaScore paginates all resources collections. By default, all requests that return multiple resources will be limited to 50 items per page. The first page is always page 1.

Page number
The page[number] query parameter allows to request a specific page number.

To get the second page of LoL champions, use the following URL:

/lol/champions?page[number]=2
Page size
The page[size] query parameters allows to change the page size.

To get LoL champions with only 10 items per page, use the following URL:

/lol/champions?page[size]=10
🚧
Warning

Pages are limited to a maximum of 100 items per page.

Navigating pages
The Link header in the HTTP response contains data to navigate between pages.

Requesting /matches/upcoming will give a response that contains the following Link header:

<https://api.pandascore.co/matches/upcoming?page=18>; rel="last", <https://api.pandascore.co/matches/upcoming?page=2>; rel="next"
As this response returned the first page, only the link to the next and last pages are provided. Depending on which page is requested, different links can be provided via the Link header:

first — link to the first page
previous — link to the previous page
next — link to the next page
last — link to the last page
Additional response headers
PandaScore responses also contain the following HTTP headers:

X-Page — the current page number
X-Per-Page — the current page length
X-Total — the total count of items

## Filtering and sorting:

"Filtering and sorting
Learn how to use URL parameters to filter and sort results from the PandaScore REST API.

Suggest Edits
PandaScore allows you to filter and sort request results by adding query parameters to the request URL.

Filter
The filter query parameter is used to check for strict equality. Using the filter[field]=value syntax, you can filter out results where field is not strictly equal to value.

To get only LoL champions whose name is Brand, use the following URL:

/lol/champions?filter[name]=Brand
The filter parameter also accepts a list of comma-separated values. To get only LoL champions whose name is either Brand or Twitch, use the following URL:

/lol/champions?filter[name]=Brand,Twitch
📘
Dates format

When using filter with dates, dates should be given in UTC time.

🚧
Warning

The filter parameter only allows to filter dates on their date (day, month, year). The time (hours, minutes, seconds) is ignored.

Search
The search query parameter is used to check for string values that contain a given sub-string. Using the search[field]=value, you can filter out results where field does not contain value.

To get only LoL champions whose name contains "twi", use the following URL:

/lol/champions?search[name]=twi
📘
Notice

The search query parameter only works with string values.

Range
The range query parameter is used to check for numeric values that are between a given interval. Using the range[field]=0,100, you can filter out results where field is not between 0 and 100.

To get only LoL champions whose HP are between 500 and 1000, use the following URL:

/lol/champions?range[hp]=500,1000
📘
Notice

The range query parameter only works with numeric values.

Sort
The sort query parameter is used to sort results based on field values. Using the sort=field syntax, you can sort results by field.

The sorting is always done in ascending order unless the field name is prefixed with a minus, in which case the order is descending.

It is possible to sort by multiple fields by providing a comma-separated list of values. In that case, the sorting is done by the first field, then the second, and so on.

The following URL allows you to:

get all LoL champions
sort champions by attack damage
sort champions with the same attack damage by their name (descending alphabetical order).

/lol/champions?sort=attackdamage,-name
📘
Null values

When sorting in ascending order, null values appear first. In descending order, null values come last."

## Disconnect: "Disconnections

Learn how to handle PandaScore WebSockets API disconnections.

Suggest Edits
When disconnecting clients from WebSockets, PandaScore sends a status code that allows your application to understand why the connection was closed.

Match finished
PandaScore opens a WebSocket for each match that is covered with live data. When the match finishes, the WebSocket will close with the 1000 code.

Client errors
When the WebSocket closes because of a client error, an error code within the 4xxx range will be sent.

Status Code Definition
4001 — Unauthorized Missing token. See Authentication.
4003 — Forbidden Socket URL is not available with your plan. See Coverage.
4029 — Too Many Connections Maximum number of simultaneous connections to a match reached (3).
Server errors
When the WebSocket closes because of a server error, an error code within the 1xxx range will be sent. This code will be different from 1000, which is reserved for normal closure. These only happen because of issues on PandaScore's servers and should be rare.

When you are disconnected because of such error, you should try to connect again. When using the Events API, you might also want to recover the missing events."

## Seasons :

Seasons and circuits
Learn the basics of how the major esports scene competitions articulate during the year.

Suggest Edits
In esports like in so-called traditional sports, different sports often mean a different competitive scene structure. This guide is intended to walk you through the basics of the major esports scenes.

Dota 2
The highest level of Dota 2 competition is organized around the Dota Pro Circuit. The Tier 1 scene is split in regional leagues: North America, South America, Western Europe, Eastern Europe, China, and Southeast Asia.

Regional leagues allow team to qualify for the Majors. Majors are international competitions that grants points in the Dota Pro Circuit. Those points allow team to qualify for the end-of-season World championship, The International.

You can read more about the Dota Pro Circuit on the community-based wiki liquipedia.net.

Learn more on DotA 2 specifics here.

Counter-Strike: Global Offensive
The highest level of Counter-Strike: Global Offensive (CS:GO) competition is organized around the Major Championships. Outside of this Tier 1 scene, a lot of independent tournament organizers create events that have teams face against each other very often throughout the year. Similarly to Tennis in traditional sports, some more well-known events contribute more to a team's ranking in the overall system.

The CS:GO esports scene is very active and teams from different regions will have different key events to participate in. We recommend taking a look at the scene structure in the regions that are part of your market. You can read more about the Major Championships and other tournaments on the community-based wiki liquipedia.net.

League of Legends
League of Legends (LoL) Tier 1 scene is organized in regional leagues: Europe, North America, China, Korea, and many others.
In each of those regions, teams play during a regular season that includes two splits: Spring and Summer. At the end of each split, there are regional Playoffs which further determine team rankings for that Split. Winners of the Spring Split have a chance to represent their team at an international event. The Summer Split Playoffs allows team to qualify for the end-of-season World championship.

Different regions may have different rules and qualification system. You can read more about the World Championships on the community-based wiki liquipedia.net.

Learn more on League of Legends specifics here.

Rocket League
Rocket League Tier 1 scene is organized in regional leagues: Europe, North America, South America, and Oceania.
In each of those regions, teams play during a regular season that includes three splits: Fall, Winter, and Spring. At the end of each split, there are regional Playoffs that allow teams to qualify for an international Major.
The season concludes with a World championship where the best teams from all regions compete.

Overwatch
The Overwatch esports scene revolves around its only Tier 1 competition, the Overwatch League. The league is built on an NBA-like model that includes two conferences (named divisions), where teams represent a city in home and away matches. New talents are promoted through a set of tier 2 region-based competitions named Overwatch Contenders.

Learn more on Overwatch specifics here.

## League of Legends

Learn how PandaScore handles scenarios specific to League of Legends esports.

Suggest Edits
League of Legends is a 5-versus-5 Multiplayer Online Battle Arena (MOBA). During a game, each player controls a character—a champion. By killing enemy champions and neutral monsters on the map, players acquire the resources necessary to gain levels and purchase items.

See list of champions with the LoL > List champions endpoint.
See list of items with the LoL > List items endpoint.

Patches & Versioning
Like many esports titles, League of Legends is regularly updated. Updates, commonly known as patches, change the balance of the game and the way it is played as a result.

In order to thoroughly cover the implications of such updates, PandaScore versions all League of Legends static resources (items, champions, etc) and provides the video game version for all matches.

Finding the differences between 2 versions of a champion

Using the LoL > List champions endpoint, we can retrieve a champion in its current state.

Making a request to /lol/champions?filter[name]=Sejuani to retrieve Sejuani gets a response like the following:

JSON

[
{
"attackdamage": 66,
"id": 2582,
// other fields are removed for clarity
"videogame_versions": [
"9.24.2",
"9.24.1",
"9.23.1",
"9.22.1"
]
}
]
The videogame_versions array show us that Sejuani was not updated from patch 9.22.1 to 9.24.2. The id 2582 will always return this version of Sejuani.

🚧
Warning

Actual results may vary. The following example uses API responses from this guide's writing time.

Using the Lol > List champions for a version endpoint, we can retrieve all champions for a given patch.

Making a request to https://api.pandascore.co/lol/versions/9.21.1/champions?filter[name]=Sejuani to retrieve Sejuani on patch 9.21.1 gets a response like the following.

JSON

[
{
"attackdamage": 64,
"id": 2533,
// other fields are removed for clarity
"videogame_versions": [
"9.21.1",
"9.20.1",
"9.19.1",
"9.18.1",
"9.17.1",
"9.16.1",
"9.15.1",
"9.14.1",
"9.13.1"
]
}
]
We can see that some fields have a different values here. In particular, this example shows that Sejuani's attack damage increased from 64 to 66 in patch 9.22.

## Authentication

Learn how to authenticate to PandaScore REST and WebSockets APIs.

Suggest Edits
Access to our APIs is restricted by token-based authentication. Your access token is available on your Dashboard.

🚧
Warning

This token is private, do not use it in client-side applications.

REST API
All requests against the REST API must be authenticated. PandaScore accepts two authentication methods: via a Bearer token set in HTTP request headers or by passing the token in the URL.

Bearer Token

You can authenticate by setting an Authorization HTTP header on your request with the value Bearer <insert your token here>.

cURL

curl --request GET \
 --url 'https://api.pandascore.co/videogames' \
 --header 'Accept: application/json' \
 --header 'Authorization: Bearer PLACEHOLDER_TOKEN_VALUE'
URL Parameter

Alternatively, you can also authenticate by passing your token via a token URL parameter.

cURL

curl --request GET \
 --url 'https://api.pandascore.co/videogames?token=PLACEHOLDER_TOKEN_VALUE' \
 --header 'Accept: application/json'
These examples use the All Video games > List video games endpoint.

WebSockets API
The WebSockets API only accepts authentication via URL parameter. Append your token in a token URL parameter when trying to connect.

Shell

wscat -c "wss://live.pandascore.co/matches/595466?token=PLACEHOLDER_TOKEN_VALUE"

## Rate and connections limits

Learn how rate limits and maximum connections can affect your PandaScore integrations.

Suggest Edits
Usage of the PandaScore APIs is restricted depending on your plan. Your plan and your current usage are available on your dashboard.

For more information on plans, see Pricing.

REST API
Usage of the REST API is restricted by a rate limit, i.e. a maximum number of requests per hour. In every API response, the number of remaining requests is available in the response X-Rate-Limit-Remaining HTTP header. Below is a table summary of the rate limit per hour for each plan:

Plan Name Rate Limit for the REST API
Schedules, Results & Context Data 1k requests per hour
Historical & Post-Match Data 10k requests per hour
Real-time Data (Basic) 10k requests per hour
Real-time Data (Pro) 10k requests per hour
Read more: REST API > Errors

WebSockets API
The WebSockets API is available to those on either the Real-time Data (Basic) or the Real-time Data (Pro) plans. Both of these plans allow a maximum of 3 simultaneous connections to a given match.

## Fundamentals

Learn the fundamentals of the opinionated, flexible data structure PandaScore uses to map esports competitions across all video games.

Suggest Edits
📘
Coming from sports?

This page uses examples from esports and traditional sports to explain PandaScore data structure.

Leagues
Leagues are the top-level data structure used to represent a competition. Leagues are commonly named after the competition they represent. A league includes one or several children Series.

Examples

FIFA World Cup
The International
Series
Series represent a single timely occurrence of their parent League. A series includes one or several children Tournaments.

Examples

FIFA World Cup — 2018
The International — 2018
Tournaments
Tournaments represent a stage in their parent Series. A tournament includes one or several children matches that contribute to a unique standing and possible winner.

Examples

FIFA World Cup — 2018 — Group C
The International — 2018 — Playoffs
Matches
Matches represent a team-versus-team or player-versus-player confrontation between two participants of a parent Tournament. A match includes one or several children Games.

Matches is the most in-depth generic data structure. Despite many common properties, the data structure for Games (and below) is specific to each video game, and game IDs are unique for each videogame.

Examples

FIFA World Cup — 2018 — Group C — Denmark vs Australia (only 1 game)
The International — 2018 — Playoffs — Final: OG vs PSG.LGD (5 games)
In-game results should be retrieved via game-level endpoints (only available for video games supporting Historical Data).

## Introduction

Get started with PandaScore APIs for real-time esports statistics.

Data coverage
PandaScore provides historical and real-time statistics for 13 major esports titles, including League of Legends, Counter-Strike, DotA2 and Valorant. The level of data coverage falls under three main categories: fixtures data, historical data and real-time data. Fixtures and historical data are available via the REST API. Real-time in-game statistics are accessible via the Live API.

To better understand the data categories available with the PandaScore APIs, this section summarises each, paired with a visual representation of example usage.

Fixtures data
Fixtures data provides an overview of esports competitions, schedules and results for all available matches.

Every match contains essential information such as the name, scheduled time, format (e.g. best of 5), team opponents and live streams for viewers to watch the games.

Fixtures updates for matches are provided in real-time, informing when a match begins or ends, the final match score and the winning team.

Utilising the fixtures plan, users can display real-time esports schedules and results, such as this:

1390
Example of a finished match using data within the Fixtures only plan.

ℹ️
The Fixtures Only plan is available to all users for free.

Historical data
Historical data displays in-depth game, team and player statistics once a game has ended, providing a detailed performance overview.

Post-game statistics are available in the Historical plan for our major esports titles: Counter-Strike, League of Legends, DotA2 and Valorant. The statistics available for each esports title will vary due to the differing nature of the gameplay. For example, in League of Legends, there are more than 50 unique data points related to player performance and over 20 unique data points related to team performance.

The following example uses a small selection of the historical player and team data points from a League of Legends game:

1118
Example of finished game using data available within the League of Legends Historical plan.

ℹ️
Post-game data is available in the Historical plan and above.

Live data
Live data delivers in-game statistics to users in real-time. This type of data is accessible via WebSockets within the Live API. There are two live data feeds available: frames and events.

Live statistics may experience delay from the actual game server when applied or requested by the event organiser or its suppliers.

Frames

The frames feed presents an overview of the game-state, displaying information typically visible from an in-game HUD, e.g. current player K/D/A.

An example of a live League of Legends game using data from the frame's feed.
An example of a live League of Legends game using data from the frame's feed.

Events

The events feed provides a detailed timeline of key moments to give a better comprehension of pivotal events in-game, e.g. a player kill event.

An example of a live League of legends game with data from the events feed.
An example of a live League of legends game with data from the events feed.

ℹ️
The events feed and a selection of statistics from the frames feed are exclusively available in the Pro Live plan. More details of both feeds can be found in our data samples documentation
