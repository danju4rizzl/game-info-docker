---
type: 'agent_requested'
description: 'Whenever working with Panda Scores API'
---

Here are the pandascore api refrence page for LOL:
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
