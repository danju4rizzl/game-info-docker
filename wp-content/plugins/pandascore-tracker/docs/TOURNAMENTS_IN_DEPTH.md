Tournaments in-depth
Learn about tournaments participants, brackets, standings and tiers in the PandaScore REST API.

Tournaments are a fundamental part of PandaScore's data structure. Matches are often retrieved at the tournament level, as tournaments are the lowest level of abstraction for child matches.

All tournaments are available via the List Tournaments endpoint, though they can also be retrieved by their status (past, ongoing and upcoming), and for more detailed tournament information, via tournament ID.

Tournament participants
To get the teams and players that are participating in a tournament, tournament rosters should be used.

Tournament rosters can be retrieved via the following endpoints:

Get a Tournament. Available in the expected_roster array.
Get Rosters for a Tournament. Available in the rosters array.
Team players vs. tournament rosters
Team-level players are not recommended to determine tournament participants. This simply represents the players currently contracted to the team, rather than indicating player tournament participation.

An example of this can be seen below. At the time of the LoL LEC Summer Playoffs 2023, player Rekkles is contracted to the Fnatic team, however, he does not participate in the tournament:

Team contracted players and tournament roster for the LEC Summer Playoffs, 2023.
Team contracted players and tournament roster for the LEC Summer Playoffs, 2023.

This example highlights the importance of using rosters as an indication of the participating teams and players for a given tournament.

Tournament brackets
Brackets are present in knockout tournaments, where match winners and losers determine the participating teams of upcoming matches. The has_bracket field at the tournament level indicates whether a tournament has a bracket.

For each match in a tournament, the Get a Tournament's Brackets endpoint contains a previous_matches array, which displays match predecessors. Each predecessor match has a match_id and type. The type indicates whether an opponent is the winner or the loser of the predecessor match_id. The previous_matches array can also be used to know the potential participants of TBD vs. TBD matches.

Example of a tournament bracket: the LoL Worlds playoffs, 2022.
Example of a tournament bracket: the LoL Worlds playoffs, 2022.

Below is a code recipe dedicated to tournament brackets. This creates a binary tree from the Get a Tournament's Brackets endpoint, in Node.js.

🌳 Mapping tournament brackets as a binary tree

Tournament standings
Standings represent participant performance and ranking in a given tournament. They can be retrieved using the Get Tournament Standings endpoint.

Example of tournament standings, for the LoL LEC Summer Regular Season, 2023.
Example of tournament standings, for the LoL LEC Summer Regular Season, 2023.

Tournament tiers
Our tiering system consists of 5 rankings: S > A > B > C > D, with S representing the highest tier and D representing the lowest tier. Tiers are assigned at the tournament level. We consider various factors to determine the tournament tiers, mainly the organizer, the level of the participating teams and the prize pool. Tournament tiers are assigned in-house, meaning tiering could differ from other sources.

S Tier
S Tier is the highest tier, only assigned to the most prestigious competitions, which determine the best of the best for each videogame. These tournaments have a prize pool ranging from $250,000 to sometimes going beyond $1,000,000. S-tier examples include the child tournaments of:

The International and Majors in DotA2.
Worlds and MSI in LoL.
Majors, IEM and BLAST Premier in CS:GO.
VCT Masters in Valorant.
A Tier
A Tier is the 2nd highest tier, where the level of competition is not as prestigious as the big names above, though it is still at a high level. A-tiered tournaments consist of international events and regional leagues, where the prize pools are still quite hefty. Examples of this tier include the child tournaments of:

The Dota Pro Circuit: Division 1 in DotA2.
LPL, LCK, LCS and LEC in LoL.
ESL Pro League and RMRs (the qualifiers for the Majors) in CS:GO.
VCT EMEA and VCT NA in Valorant.
B Tier
B Tier is the middle-range tier and is probably the least common, as very few tournaments reach the mark: most are either above or below it. Examples of this tier include:

The International Qualifiers in DotA2.
Riot organized tournaments, belonging to leagues such as TCL, LLA, CBLOL and LJL in LoL.
Elisa Invitational, DreamHack, and qualifiers to S and A tiered tournaments in CS:GO.
C Tier
C Tier tournaments are somewhat low profile, usually planned by independent organizers. Prize pools can be up to $75k in these types of tournaments. Additionally, there will be a couple of mid-level teams invited to play. These factors keep them in this tier and not below. C-tiered examples include the child tournaments of:

Dota Pro Circuit: Division 2 and Champions League in DotA2.
EU Regional leagues such as LFL, Prime, Ultraliga and NLC in LoL.
Champion of Champions Tour, WePlay Academy and ESL Impact in CS:GO.
D Tier
D Tier consists of very low-profile tournaments. Prize pools are rarely above $10k. D tier is likely the most common tier in CS:GO due to the number of esports tournaments played. Similarly, most of the Valorant regions fall under this tier as well. It is less common in LoL and DotA2 due to the seasonal structure of the videogames, though there will still be a few tournaments with this tier during the off-season.

Tournament tiers in the API
Tiers are available in the REST API as the data point tier for tournament objects.

Tournaments within the API can be filtered by tier using the optional query parameter filter[tier]. For example, the following API call returns only S-tiered tournaments:

https://api.pandascore.co/tournaments?filter[tier]=s

Additionally, this query parameter can take multiple tiers to filter by. For example, the API call below returns both S and A tier tournaments:

https://api.pandascore.co/tournaments?filter[tier]=s,a

Further information regarding filters in the REST API is available in our Filtering and Sorting overview.