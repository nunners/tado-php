# tado-php
API Integration with Tado - Heating controls


This is a class based PHP setup which aims to utilise the Tado API to pull data about your heating systems. I am using it to mainly track temperatures and humidity at home, but will expand it to also include other metrics that you can pull off the Tado API. 

I've used the following pages as reference for building this up:
- https://shkspr.mobi/blog/2019/02/tado-api-guide-updated-for-2019/
- https://www.openhab.org/addons/bindings/tado/

Current Class end points:
- getZones / listZones - returns a list of Zones, and whether they're heating or other
- getZoneIdFromName
- 
- getZoneStateFromId - returns the full state of the zone from the Zone ID
- getZoneStateFromName - returns the full state of the zone from the Zone name
- zone_temperature - returns the Zone Temperature based on the zone name
- zone_humidity - returns the Zone humidity based on the zone name
- zone_heating_target - returns the Zone Heating Target based on the zone name
- zone_heating_power - returns the Zone Heating Power based on the zone name
- zone_hot_water_state - returns the hot water state based on the zone name
- getCurrentSetting - current setting for the zone based on the zone name
- getNextScheduleChange - time the schedule is changing for the zone

Config:
- you will need to add your Username and Password to the tado.class.php file
