<!DOCTYPE html>
<html>

<head>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar/index.global.min.js'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar')
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                
                    events: [{
                            title: 'Event1',
                            start: '2011-04-04'
                        },
                        {
                            title: 'Event2',
                            start: '2011-05-05'
                        }
                        // etc...
                    ],
                    color: 'yellow', // an option!
                    textColor: 'black' // an option!
                
            })
            calendar.render()
        })
    </script>
</head>

<body>
    <div id='calendar'></div>
</body>

</html>