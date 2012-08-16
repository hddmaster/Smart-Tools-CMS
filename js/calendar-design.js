// путь к картинкам
var imagesFolder = '/admin/images/calendar/';


// *********************************** Calendar ***********************************
function calendarHTML(month, year, currDate) {
    // смотрим этот ли месяц показываем
    var isThisMonth = (currDate)? (currDate.getMonth() == month && currDate.getFullYear() == year) : false;

    // генерирует html-код для указанного месяца
    // устанавливаем месяц, который будем рисовать
    var drawMonth = new Date();
    drawMonth.setMonth(month, 1);
    drawMonth.setYear(year);
    drawMonth.setDate(1);
    
    // переменные для кнопок навигации по месяцам/годам
    var thisMonth = drawMonth.getMonth();
    var nextMonth = (thisMonth == 11)? 0 : thisMonth + 1;
    var prevMonth = (thisMonth == 0)? 11 : thisMonth - 1;
    
    var thisYear = drawMonth.getFullYear();
    var nextYear = thisYear + 1;
    var prevYear = thisYear - 1;
    var nextMonthYear = (thisMonth == 11)? thisYear + 1 : thisYear;
    var prevMonthYear = (thisMonth == 0)? thisYear - 1 : thisYear;
    
    
    // запихиваем в строку весь код - открываем таблицы...
    var calendarCode = '<table width="150" border="0" cellspacing="0" cellpadding="0" style="border: 1px solid #D7D7D7;">';
    calendarCode += '<tr><td class="purpleCell"><table cellpadding="0" cellspacing="3" border="0" width="150">';
/*    
    // здесь указываем клик на прошлый год
    calendarCode += '<tr><td><img src="' + imagesFolder + 'arr-prev.gif" width="15" height="16" border="0" onClick="switchMonthTo(' + thisMonth + ', ' + prevYear + ')" style="cursor: pointer; cursor: hand;" /><\/td>';
    // текущий (показываемый) год
    calendarCode += '<td align="center" class="purpleCell">' + thisYear + '<\/td>';
    // клик на следующий год
    calendarCode += '<td align="right"><img src="' + imagesFolder + 'arr-next.gif" width="15" height="16" border="0" onClick="switchMonthTo(' + thisMonth + ', ' + nextYear + ')" style="cursor: pointer; cursor: hand;" /><\/td><\/tr>';
*/    
    // клик на предыдущий месяц
    calendarCode += '<tr><td><img src="' + imagesFolder + 'arr-prev.gif" width="15" height="16" border="0" onClick="switchMonthTo(' + prevMonth + ', ' + prevMonthYear + '); return false;" style="cursor: pointer; cursor: hand;" /><\/td>';
    // текущий месяц
    calendarCode += '<td align="center" class="purpleCell">' + calendarNames.month[thisMonth] + ', ' + thisYear + '<\/td>';
    // клик на следующий месяц
    calendarCode += '<td align="right"><img src="' + imagesFolder + 'arr-next.gif" width="15" height="16" border="0" onClick="switchMonthTo(' + nextMonth + ', ' + nextMonthYear + '); return false;" style="cursor: pointer; cursor: hand;" /><\/td><\/tr>';
    calendarCode += '<\/table><\/td><\/tr>';



    // начинаем таблицу самого месяца
    calendarCode += '<tr><td style="border-top: 1px solid #D7D7D7;" bgcolor="#ffffff"><table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-bottom: 1px solid #D7D7D7;"><tr>';
    calendarCode += '<td class="whiteCell"><br /><\/td><td class="whiteCell"><br /><\/td>';
    for (var i = 0; i < calendarNames.weekday.length; i++) {
        var styleClass = (i < calendarNames.weekday.length - 1)? 'whiteCell' : 'sundayCell';
        calendarCode += '<td class="weekDay" align="right">' + calendarNames.weekday[i] + '<\/td>';
    }
    calendarCode += '<td class="whiteCell"><br /><\/td><td class="whiteCell"><br /><\/td><\/tr>';

    // сам месяц
    calendarCode += '<tr><td class="whiteCell"><br /><\/td><td class="whiteCell"><br /><\/td>';
    // рисуем пустые ячейки если нужно...
    var daysToStart = (drawMonth.getDay() == 0)? 7 : drawMonth.getDay();
    for (var i = 0; i < daysToStart - 1; i++) calendarCode += '<td class="whiteCell"><br /><\/td>';

    // собственно циферки
    for (var i = 1; i < 33; i++) {
        drawMonth.setDate(i);
        if (isThisMonth && i == currDate.getDate()) {
            calendarCode += '<td class="blackCell" align="right" onClick="setDateForCalendar(' + i + ', ' + month + ', ' + year + '); return false;" style="cursor: pointer; cursor: hand;">' + i + '<\/td>'
        }
        else {
            if (drawMonth.getMonth() == thisMonth) {
                var styleClass = ((drawMonth.getDay()==0) || (drawMonth.getDay()==6))? 'sundayCell' : 'whiteCell'
                calendarCode += '<td class="' + styleClass + '" align="right" onMouseOver="this.className = \'overCell\';" onMouseOut="this.className = \'' + styleClass + '\';" onClick="setDateForCalendar(' + i + ', ' + month + ', ' + year + '); return false;" style="cursor: pointer; cursor: hand;">' + i + '<\/td>';
            }
            else {
                break;
            }
        }
        if (drawMonth.getDay() == 0) calendarCode += '<td class="whiteCell"><br /><\/td><td class="whiteCell"><br /><\/td><\/tr><tr><td class="whiteCell"><br /><\/td><td class="whiteCell"><br /><\/td>';
    }

    // опять рисуем пустые ячейки
    if (drawMonth.getDay() != 1) {
        var daysToEnd = 8 - ((drawMonth.getDay() == 0)? 7 : drawMonth.getDay());
        for (var i = 0; i < daysToEnd; i++) calendarCode += '<td class="whiteCell"><br /><\/td>';
    }
    calendarCode += '<td class="whiteCell"><br /><\/td><td class="whiteCell"><br /><\/td><\/tr><\/table><\/td><\/tr>';
    // ссылка на сегодня
    calendarCode += '<tr><td class="whiteCell" onMouseOver="this.className = \'overCell\';" onMouseOut="this.className = \'whiteCell\';" style="padding: 6px; cursor: pointer; cursor: hand;" align="center" onClick="setTodayFromCalendar(); return false;">'+calendarNames.today+'<\/td><\/tr>';
    // конец
    calendarCode += '<\/table>';


    return calendarCode;
}
