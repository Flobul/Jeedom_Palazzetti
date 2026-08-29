/* This file is part of Jeedom. */

(function() {
    'use strict';

    const root = document.getElementById('div_palazzettiHeatingHistory');
    if (!root) return;

    const equipment = root.querySelector('#sel_palazzettiHistoryEquipment');
    const startInput = root.querySelector('#in_palazzettiHistoryStart');
    const endInput = root.querySelector('#in_palazzettiHistoryEnd');
    const loadButton = root.querySelector('#bt_loadPalazzettiHeatingHistory');
    const alertContainer = root.querySelector('#div_palazzettiHistoryAlert');
    const summary = root.querySelector('#div_palazzettiHistorySummary');
    const timelineContainer = root.querySelector('#div_palazzettiHeatingChart');
    const dailyChartContainer = root.querySelector('#div_palazzettiDailyChart');
    const dailyTableBody = root.querySelector('#table_palazzettiDailyHistory tbody');

    if (!equipment || !loadButton || equipment.options.length === 0) return;

    let timelineChart = null;
    let dailyChart = null;

    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    function setPreset(days, selectedButton) {
        const end = new Date();
        const start = new Date(end.getFullYear(), end.getMonth(), end.getDate());
        start.setDate(start.getDate() - Math.max(0, days - 1));
        startInput.value = formatDate(start);
        endInput.value = formatDate(end);
        root.querySelectorAll('[data-history-days]').forEach(function(button) {
            button.classList.toggle('active', button === selectedButton);
        });
    }

    function setBusy(busy) {
        loadButton.disabled = busy;
        loadButton.innerHTML = busy
            ? '<i class="fas fa-spinner fa-spin"></i> {{Chargement…}}'
            : '<i class="fas fa-chart-area"></i> {{Afficher}}';
    }

    function showAlert(message, level) {
        alertContainer.replaceChildren();
        if (!message) return;
        const alert = document.createElement('div');
        alert.className = 'alert alert-' + (level || 'info');
        alert.textContent = message;
        alertContainer.appendChild(alert);
    }

    function formatDuration(minutes) {
        const total = Math.max(0, Math.round(Number(minutes) || 0));
        const hours = Math.floor(total / 60);
        const remaining = total % 60;
        if (hours === 0) return remaining + ' min';
        return hours + ' h ' + String(remaining).padStart(2, '0') + ' min';
    }

    function formatTemperature(value) {
        return value == null ? '—' : Number(value).toLocaleString(undefined, {maximumFractionDigits: 2}) + ' °C';
    }

    function seriesDefinition(source, options, replacementPoints) {
        const points = Array.isArray(replacementPoints) ? replacementPoints : (source && source.points);
        if (!source || !Array.isArray(points) || points.length === 0) return null;
        return Object.assign({
            name: source.name,
            data: points,
            tooltip: {valueSuffix: source.unit ? ' ' + source.unit : ''},
            turboThreshold: 0
        }, options);
    }

    function keepPointsDuringHeating(points, sessions) {
        if (!Array.isArray(points) || !Array.isArray(sessions) || sessions.length === 0) return [];
        const result = [];
        let pointIndex = 0;
        let currentValue = null;

        sessions.forEach(function(session) {
            const start = Number(session.start);
            const end = Number(session.end);
            while (pointIndex < points.length && Number(points[pointIndex][0]) <= start) {
                currentValue = Number(points[pointIndex][1]);
                pointIndex++;
            }
            if (Number.isFinite(currentValue)) {
                result.push([start, currentValue]);
            }
            while (pointIndex < points.length && Number(points[pointIndex][0]) < end) {
                currentValue = Number(points[pointIndex][1]);
                if (Number.isFinite(currentValue)) {
                    result.push([Number(points[pointIndex][0]), currentValue]);
                }
                pointIndex++;
            }
            if (Number.isFinite(currentValue)) {
                result.push([end, currentValue]);
                result.push([end + 1, null]);
            }
        });
        return result;
    }

    function drawTimeline(result) {
        if (typeof Highcharts === 'undefined') {
            showAlert('{{Le moteur graphique Highcharts de Jeedom n’est pas disponible.}}', 'danger');
            return;
        }
        if (timelineChart) timelineChart.destroy();

        const data = result.series || {};
        const sessions = Array.isArray(result.sessions) ? result.sessions : [];
        const chartSeries = [
            seriesDefinition(data.temperature, {color: '#2f7ed8', lineWidth: 2, yAxis: 0}),
            seriesDefinition(data.setpoint, {color: '#e44d26', dashStyle: 'ShortDash', step: 'left', yAxis: 0}),
            seriesDefinition(data.temperature2, {color: '#8bbc21', visible: false, yAxis: 0}),
            seriesDefinition(data.temperature3, {color: '#910000', visible: false, yAxis: 0}),
            seriesDefinition(data.power, {
                type: 'area', color: '#f28f43', fillOpacity: .3, step: 'left', lineWidth: 1, yAxis: 1
            }, keepPointsDuringHeating(data.power && data.power.points, sessions)),
            seriesDefinition(data.fan, {
                type: 'area', color: '#8085e9', fillOpacity: .25, step: 'left', lineWidth: 1, yAxis: 2
            }, keepPointsDuringHeating(data.fan && data.fan.points, sessions))
        ].filter(Boolean);
        const plotBands = sessions.map(function(session, index) {
            return {
                from: Number(session.start),
                to: Number(session.end),
                color: index % 2 === 0 ? 'rgba(242, 143, 67, .14)' : 'rgba(242, 143, 67, .22)',
                zIndex: 0
            };
        });
        const equipmentTitle = result.equipment.objectName
            ? result.equipment.objectName + ' — ' + result.equipment.name
            : result.equipment.name;
        const visibleStart = Number(result.range && result.range.startTimestamp);
        const visibleEnd = Number(result.range && result.range.endTimestamp);
        const options = {
            chart: {height: 430, zoomType: 'x'},
            time: {useUTC: false},
            title: {text: equipmentTitle},
            credits: {enabled: false},
            legend: {enabled: true},
            xAxis: {
                type: 'datetime',
                ordinal: false,
                min: Number.isFinite(visibleStart) ? visibleStart : undefined,
                max: Number.isFinite(visibleEnd) ? visibleEnd : undefined,
                plotBands: plotBands
            },
            yAxis: [{
                title: {text: '{{Température (°C)}}', style: {color: '#2f7ed8'}},
                opposite: false,
                startOnTick: false
            }, {
                title: {text: '{{Puissance}}', style: {color: '#f28f43'}},
                min: 0,
                max: 5,
                tickInterval: 1,
                opposite: true
            }, {
                title: {text: '{{Ventilateur}}', style: {color: '#8085e9'}},
                min: 0,
                max: 7,
                tickInterval: 1,
                opposite: true,
                offset: 55
            }],
            tooltip: {shared: true, xDateFormat: '%A %e %B %Y, %H:%M'},
            plotOptions: {
                series: {marker: {enabled: false}, connectNulls: false},
                area: {threshold: 0}
            },
            series: chartSeries
        };
        timelineChart = Highcharts.chart(timelineContainer, options);
        if (Number.isFinite(visibleStart) && Number.isFinite(visibleEnd) && visibleEnd > visibleStart) {
            timelineChart.xAxis[0].setExtremes(visibleStart, visibleEnd, true, false);
        }
    }

    function drawDailyComparison(rows, pelletUnit) {
        if (typeof Highcharts === 'undefined') return;
        if (dailyChart) dailyChart.destroy();
        dailyChart = Highcharts.chart(dailyChartContainer, {
            chart: {height: 260, zoomType: 'x'},
            time: {useUTC: false},
            title: {text: null},
            credits: {enabled: false},
            xAxis: {
                categories: rows.map(function(row) {
                    return new Date(row.date + 'T12:00:00').toLocaleDateString(undefined, {weekday: 'short', day: '2-digit', month: '2-digit'});
                })
            },
            yAxis: [{
                title: {text: '{{Durée (h)}}'},
                min: 0
            }, {
                title: {text: '{{Pellets consommés}}' + (pelletUnit ? ' (' + pelletUnit + ')' : '')},
                min: 0,
                opposite: true
            }],
            tooltip: {
                shared: true,
                positioner: function(labelWidth, labelHeight, point) {
                    const chart = this.chart;
                    const categoryX = point && Number.isFinite(point.plotX)
                        ? chart.plotLeft + point.plotX
                        : chart.plotLeft + (chart.plotWidth / 2);
                    const pointY = point && Number.isFinite(point.plotY)
                        ? chart.plotTop + point.plotY
                        : chart.plotTop + (chart.plotHeight / 2);
                    const centeredX = categoryX - (labelWidth / 2);
                    const preferredY = pointY - labelHeight - 12;
                    return {
                        x: Math.max(4, Math.min(centeredX, chart.chartWidth - labelWidth - 4)),
                        y: Math.max(
                            chart.plotTop + 4,
                            Math.min(preferredY, chart.plotTop + chart.plotHeight - labelHeight - 4)
                        )
                    };
                }
            },
            plotOptions: {
                column: {grouping: true, borderWidth: 0, maxPointWidth: 18}
            },
            series: [{
                type: 'column',
                name: '{{Durée de chauffe}}',
                color: '#f28f43',
                data: rows.map(function(row) {
                    const minutes = row.heatingDurationMinutes == null ? row.durationMinutes : row.heatingDurationMinutes;
                    return Math.round((Number(minutes) / 60) * 100) / 100;
                }),
                tooltip: {valueSuffix: ' h'}
            }, {
                type: 'column',
                name: '{{Pellets consommés}}',
                color: '#8bbc21',
                yAxis: 1,
                data: rows.map(function(row) { return row.pelletConsumption == null ? null : Number(row.pelletConsumption); }),
                tooltip: {valueSuffix: pelletUnit ? ' ' + pelletUnit : ''}
            }]
        });
    }

    function renderDailyTable(rows, pelletUnit) {
        dailyTableBody.replaceChildren();
        rows.forEach(function(row) {
            const tr = document.createElement('tr');
            const values = [
                new Date(row.date + 'T12:00:00').toLocaleDateString(),
                formatDuration(row.heatingDurationMinutes == null ? row.durationMinutes : row.heatingDurationMinutes),
                row.cycleCount,
                row.pelletConsumption == null
                    ? '—'
                    : Number(row.pelletConsumption).toLocaleString(undefined, {maximumFractionDigits: 3}) + (pelletUnit ? ' ' + pelletUnit : ''),
                formatTemperature(row.temperatureMin),
                formatTemperature(row.temperatureAverage),
                formatTemperature(row.temperatureMax)
            ];
            values.forEach(function(value) {
                const td = document.createElement('td');
                td.textContent = value;
                tr.appendChild(td);
            });
            dailyTableBody.appendChild(tr);
        });
    }

    function renderResult(result) {
        const summaryData = result.summary || {};
        summary.hidden = false;
        root.querySelector('#span_palazzettiHistoryDuration').textContent = formatDuration(summaryData.durationMinutes);
        root.querySelector('#span_palazzettiHistoryCycles').textContent = String(summaryData.cycleCount || 0);
        root.querySelector('#span_palazzettiHistoryDailyAverage').textContent = formatDuration(summaryData.averageDailyMinutes);

        const status = result.series && result.series.status;
        if (!status || !status.historized) {
            showAlert('{{L’historique de l’état du poêle n’est pas actif : les plages horaires et les cycles ne peuvent pas être calculés. La comparaison quotidienne reste disponible grâce aux compteurs.}}', 'warning');
        } else if (!status.points.length) {
            showAlert('{{Aucun état historisé n’est disponible sur cette période. Les plages horaires sont donc absentes, mais les compteurs peuvent encore alimenter la comparaison quotidienne.}}', 'warning');
        } else if (!(result.series.temperature && result.series.temperature.points.length)) {
            showAlert('{{Aucune température ambiante historisée n’est disponible sur cette période.}}', 'warning');
        } else if (!(result.series.pellets && result.series.pellets.historized)) {
            showAlert('{{L’historique de la quantité de pellets n’est pas actif : la consommation quotidienne ne peut pas être calculée.}}', 'warning');
        } else {
            showAlert('', 'info');
        }

        drawTimeline(result);
        const pelletUnit = result.series && result.series.pellets ? result.series.pellets.unit : '';
        renderDailyTable(result.daily || [], pelletUnit);
        drawDailyComparison(result.daily || [], pelletUnit);
    }

    function loadHistory() {
        if (!startInput.value || !endInput.value) {
            showAlert('{{Sélectionnez une date de début et une date de fin.}}', 'warning');
            return;
        }
        setBusy(true);
        showAlert('{{Lecture des historiques Jeedom…}}', 'info');
        domUtils.ajax({
            type: 'POST',
            url: 'plugins/Palazzetti/core/ajax/Palazzetti.ajax.php',
            dataType: 'json',
            timeout: 30000,
            data: {
                action: 'getHeatingHistory',
                id: equipment.value,
                date_start: startInput.value,
                date_end: endInput.value
            },
            error: function(request, status, error) {
                setBusy(false);
                handleAjaxError(request, status, error);
            },
            success: function(data) {
                setBusy(false);
                if (data.state !== 'ok') {
                    showAlert(String(data.result || '{{Impossible de lire les historiques.}}'), 'danger');
                    return;
                }
                renderResult(data.result || {});
            }
        });
    }

    root.querySelectorAll('[data-history-days]').forEach(function(button) {
        button.addEventListener('click', function() {
            setPreset(Number(button.dataset.historyDays), button);
            loadHistory();
        });
    });
    loadButton.addEventListener('click', loadHistory);
    equipment.addEventListener('change', loadHistory);
    loadHistory();
})();
