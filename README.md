# Tracker
Tracking hours for invoicing with description.

## Implementation details

### htmx-lite

This webapp uses a light version of the "htmx" philosophy - implemented in an own project, reused and modified here.

Basically, all input from the user is translated into a POST request to the same php file with a different path. 
This gets processed and the reponse contains a sequence of top level html elements including script elements.
Attributes tell where to place them in the DOM.

### Timesheet

Registering new events will end the previous one, but only the start time is recorded.
The duration on that activity gets accumulated and rounded up to the next 0.5 hours. 
The difference to the previous day is then taken as the daily accounted value.
Then, those values are aggregated to the parent project.

## https test connection

Use
~~~SH
stunnel proxy.cnf
~~~