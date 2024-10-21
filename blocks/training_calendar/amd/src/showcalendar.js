define(['jquery', 'jqueryui', 'block_training_calendar/moment', 'block_training_calendar/fullcalendar'], function ($, jqui, moment, fullCalendar) {
var wwwroot = M.cfg.wwwroot;
 
    function initManage() {
        $('#calendar').fullCalendar({  
        header: {
         left: 'prev',
         center: 'title',
         right: 'next'
        },
        weekends: true,
        slotDuration: '00:30:00',
        allDaySlot: false,        
        axisFormat: 'h:mm', 
        defaultDate: new Date(),
        selectable: true,
        defaultView: 'month',
        eventLimit: true,
        /*events: {
            url:M.cfg.wwwroot +"/blocks/training_calendar/events.php",
            type: 'POST',
            data: {
                statustype:  $('#id_eventstatus').val();
            }
        },*/
        //events: M.cfg.wwwroot +"/blocks/training_calendar/events.php",
        cache: true,        
        eventRender: function eventRender( event,element ) {
         var resp = '';
         resp += event.content;
         element.find('.fc-title').html(resp);
        return ['all', event.status].indexOf($('#id_eventstatus').val()) >= 0;
        },
        loading: function (bool) {
         if (bool)
             $('#loading').show();
         else
             $('#loading').hide();
        }
        });

        $('#id_eventstatus').on('change',function(){
          jq_conflict = jQuery.noConflict(false);
          jq_conflict("#id").hide();
          var events = {
                url: M.cfg.wwwroot +"/blocks/training_calendar/events.php",
                type: 'POST',
                data: {
                    statustype:  $('#id_eventstatus').val()
                }
          }
          $('#calendar').fullCalendar('removeEventSource', events);
          $('#calendar').fullCalendar('addEventSource', events);
          $('#calendar').fullCalendar('refetchEvents');
          //$('#calendar').fullCalendar('rerenderEvents', events);
          
       }).change();      
    }

    return {
        init: function () {
            initManage();
        }
    };
});
