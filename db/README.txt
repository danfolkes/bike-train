Bike Train uses SQLite

You can edit the database using this software:
http://sourceforge.net/projects/sqlitebrowser/

To create a new Bike Route:
Insert a new row in the Route Table:
  id: Autogenerated
  name: Start-End
  description:optional

Then, enter at least two rows into the Waypoints table:
  id: autogenerated
  routeid: the id from the above Route table
  position:
    0=start
    1=end
    2 or higher=this is used to correct Google's auto generated directions from start to finish.
  description: optional
  name: Short name. Example: Elwoods Cafe
  point: This is the address or lat,long.
    Your point should show up correctly by searching for it on Google Maps.
    If you need to find lat/long:
      http://itouchmap.com/latlong.html

After you do that, you should be able to 
save and upload it and it should appear on the map.  
 ** You may have to clear your browser cache. 

Test out the route by logging in and adding the route to your list of routes.