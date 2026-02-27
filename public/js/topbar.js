(function () {
    var DAYS = ['DOM', 'SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SÁB'];
    var MONTHS = ['JAN','FEV','MAR','ABR','MAI','JUN','JUL','AGO','SET','OUT','NOV','DEZ'];

    // 1. DATA — dia da semana + dia/mês (atualiza à meia-noite)
    function updateDate() {
        var now = new Date();
        var day = DAYS[now.getDay()];
        var d = String(now.getDate()).padStart(2, '0');
        var mon = MONTHS[now.getMonth()];
        document.getElementById('jfx-date').textContent = day + ', ' + d + '/' + mon;
    }
    updateDate();
    // reagenda para a próxima meia-noite
    function scheduleNextDay() {
        var now = new Date();
        var msUntilMidnight = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1) - now;
        setTimeout(function () { updateDate(); scheduleNextDay(); }, msUntilMidnight);
    }
    scheduleNextDay();

    // 2. FUSO HORÁRIO — offset UTC do navegador (ex: UTC-3)
    var offsetMin = new Date().getTimezoneOffset(); // positivo = atrás do UTC
    var sign = offsetMin <= 0 ? '+' : '-';
    var hours = Math.floor(Math.abs(offsetMin) / 60);
    document.getElementById('jfx-tz').textContent = 'UTC' + sign + hours;

    // 3. RELÓGIO AO VIVO
    function updateClock() {
        var now = new Date();
        var h = String(now.getHours()).padStart(2, '0');
        var m = String(now.getMinutes()).padStart(2, '0');
        var s = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('jfx-clock').textContent = h + ':' + m + ':' + s;
    }
    updateClock();
    setInterval(updateClock, 1000);
})();
