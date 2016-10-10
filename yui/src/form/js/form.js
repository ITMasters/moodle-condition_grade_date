/**
 * JavaScript for form editing grade conditions.
 *
 * @module moodle-availability_grade_date-form
 */
M.availability_grade_date = M.availability_grade_date || {};

/**
 * @class M.availability_grade_date.form
 * @extends M.core_availability.plugin
 */
M.availability_grade_date.form = Y.Object(M.core_availability.plugin);

/**
 * Grade items available for selection.
 *
 * @property grades
 * @type Array
 */
M.availability_grade_date.form.grades = null;

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {Array} grades Array of objects containing gradeid => name
 */
M.availability_grade_date.form.initInner = function(grades, html, defaultTime) {
    this.grades = grades;
    this.nodesSoFar = 0;
    this.html = html;
    this.defaultTime = defaultTime;
};

M.availability_grade_date.form.getNode = function(json) {
    // Increment number used for unique ids.
    this.nodesSoFar++;
    // Create HTML structure.
    var html = '<label>' + M.util.get_string('title', 'availability_grade_date') + ' <span class="availability-group">' +
            '<select name="id"><option value="0">' + M.util.get_string('choosedots', 'moodle') + '</option>';
    for (var i = 0; i < this.grades.length; i++) {
        var grade = this.grades[i];
        // String has already been escaped using format_string.
        html += '<option value="' + grade.id + '">' + grade.name + '</option>';
    }
    html += '</select></span></label> <span class="availability-group">' +
            '<label><input type="checkbox" name="min"/>' + M.util.get_string('option_min', 'availability_grade_date') +
            '</label> <label><span class="accesshide">' + M.util.get_string('label_min', 'availability_grade_date') +
            '</span><input type="text" name="minval" title="' +
            M.util.get_string('label_min', 'availability_grade_date') + '"/></label>%</span>' +
            '<span class="availability-group">' +
            '<label><input type="checkbox" name="max"/>' + M.util.get_string('option_max', 'availability_grade_date') +
            '</label> <label><span class="accesshide">' + M.util.get_string('label_max', 'availability_grade_date') +
            '</span><input type="text" name="maxval" title="' +
            M.util.get_string('label_max', 'availability_grade_date') + '"/></label>%</span><br/>';
    
    html += M.util.get_string('direction_before', 'availability_grade_date') + ' <span class="availability-group">' +
            '<label><span class="accesshide">' + M.util.get_string('direction_label', 'availability_grade_date') + ' </span>' +
            '<select name="direction">' +
            '<option value="&gt;=">' + M.util.get_string('direction_from', 'availability_grade_date') + '</option>' +
            '<option value="&lt;">' + M.util.get_string('direction_until', 'availability_grade_date') + '</option>' +
            '</select></label></span> ' + this.html;
    var node = Y.Node.create('<span>' + html + '</span>');

    // Set initial values.
    if (json.id !== undefined &&
            node.one('select[name=id] > option[value=' + json.id + ']')) {
        node.one('select[name=id]').set('value', '' + json.id);
    }
    if (json.min !== undefined) {
        node.one('input[name=min]').set('checked', true);
        node.one('input[name=minval]').set('value', json.min);
    }
    if (json.max !== undefined) {
        node.one('input[name=max]').set('checked', true);
        node.one('input[name=maxval]').set('value', json.max);
    }
    if (json.t !== undefined) {
        node.setData('time', json.t);
        // Disable everything.
        node.all('select:not([name=direction])').each(function(select) {
            select.set('disabled', true);
        });

        var url = M.cfg.wwwroot + '/availability/condition/grade_date/ajax.php?action=fromtime' +
            '&time=' + json.t;
        Y.io(url, { on : {
            success : function(id, response) {
                var fields = Y.JSON.parse(response.responseText);
                for (var field in fields) {
                    var select = node.one('select[name=x\\[' + field + '\\]]');
                    select.set('value', '' + fields[field]);
                    select.set('disabled', false);
                }
            },
            failure : function() {
                window.alert(M.util.get_string('ajaxerror', 'availability_grade_date'));
            }
        }});
    } else {
        // Set default time that corresponds to the HTML selectors.
        node.setData('time', this.defaultTime);
    }
    if (json.d !== undefined) {
        node.one('select[name=direction]').set('value', json.d);
    }
    // Disables/enables text input fields depending on checkbox.
    var updateCheckbox = function(check, focus) {
        var input = check.ancestor('label').next('label').one('input');
        var checked = check.get('checked');
        input.set('disabled', !checked);
        if (focus && checked) {
            input.focus();
        }
        return checked;
    };
    node.all('input[type=checkbox]').each(updateCheckbox);

    // Add event handlers (first time only).
    if (!M.availability_grade_date.form.addedEvents) {
        M.availability_grade_date.form.addedEvents = true;

        var root = Y.one('#fitem_id_availabilityconditionsjson');
        root.delegate('change', function() {
            // For the grade item, just update the form fields.
            M.core_availability.form.update();
        }, '.availability_grade_date select[name=id]');

        root.delegate('click', function() {
            updateCheckbox(this, true);
            M.core_availability.form.update();
        }, '.availability_grade_date input[type=checkbox]');

        root.delegate('valuechange', function() {
            // For grade values, just update the form fields.
            M.core_availability.form.update();
        }, '.availability_grade_date input[type=text]');

        root.delegate('change', function() {
            // For the direction, just update the form fields.
            M.core_availability.form.update();
        }, '.availability_grade_date select[name=direction]');

        root.delegate('change', function() {
            // Update time using AJAX call from root node.
            M.availability_grade_date.form.updateTime(this.ancestor('span.availability_grade_date'));
        }, '.availability_grade_date select:not([name=direction])');
    }
    if (node.one('a[href=#]')) {
        // Add the date selector magic.
        M.form.dateselector.init_single_date_selector(node);

        // This special handler detects when the date selector changes the year.
        var yearSelect = node.one('select[name=x\\[year\\]]');
        var oldSet = yearSelect.set;
        yearSelect.set = function(name, value) {
            oldSet.call(yearSelect, name, value);
            if (name === 'selectedIndex') {
                // Do this after timeout or the other fields haven't been set yet.
                setTimeout(function() {
                    M.availability_grade_date.form.updateTime(node);
                }, 0);
            }
        };
    }
    return node;
};

/**
 * Updates time from AJAX. Whenever the field values change, we recompute the
 * actual time via an AJAX request to Moodle.
 *
 * This will set the 'time' data on the node and then update the form, once it
 * gets an AJAX response.
 *
 * @method updateTime
 * @param {Y.Node} component Node for plugin controls
 */
M.availability_grade_date.form.updateTime = function(node) {
    // After a change to the date/time we need to recompute the
    // actual time using AJAX because it depends on the user's
    // time zone and calendar options.
    var url = M.cfg.wwwroot + '/availability/condition/date/ajax.php?action=totime' +
            '&year=' + node.one('select[name=x\\[year\\]]').get('value') +
            '&month=' + node.one('select[name=x\\[month\\]]').get('value') +
            '&day=' + node.one('select[name=x\\[day\\]]').get('value') +
            '&hour=' + node.one('select[name=x\\[hour\\]]').get('value') +
            '&minute=' + node.one('select[name=x\\[minute\\]]').get('value');
    Y.io(url, { on : {
        success : function(id, response) {
            node.setData('time', response.responseText);
            M.core_availability.form.update();
        },
        failure : function() {
            window.alert(M.util.get_string('ajaxerror', 'availability_grade_date'));
        }
    }});
};

M.availability_grade_date.form.fillValue = function(value, node) {
    value.id = parseInt(node.one('select[name=id]').get('value'), 10);
    if (node.one('input[name=min]').get('checked')) {
        value.min = this.getValue('minval', node);
    }
    if (node.one('input[name=max]').get('checked')) {
        value.max = this.getValue('maxval', node);
    }
    value.d = node.one('select[name=direction]').get('value');
    value.t = parseInt(node.getData('time'), 10);
};

/**
 * Gets the numeric value of an input field. Supports decimal points (using
 * dot or comma).
 *
 * @method getValue
 * @return {Number|String} Value of field as number or string if not valid
 */
M.availability_grade_date.form.getValue = function(field, node) {
    // Get field value.
    var value = node.one('input[name=' + field + ']').get('value');

    // If it is not a valid positive number, return false.
    if (!(/^[0-9]+([.,][0-9]+)?$/.test(value))) {
        return value;
    }

    // Replace comma with dot and parse as floating-point.
    var result = parseFloat(value.replace(',', '.'));
    if (result < 0 || result > 100) {
        return value;
    } else {
        return result;
    }
};

M.availability_grade_date.form.fillErrors = function(errors, node) {
    var value = {};
    this.fillValue(value, node);

    // Check grade item id.
    if (value.id === 0) {
        errors.push('availability_grade_date:error_selectgradeid');
    }

    // Check numeric values.
    if ((value.min !== undefined && typeof(value.min) === 'string') ||
            (value.max !== undefined && typeof(value.max) === 'string')) {
        errors.push('availability_grade_date:error_invalidnumber');
    } else if (value.min !== undefined && value.max !== undefined &&
            value.min >= value.max) {
        errors.push('availability_grade_date:error_backwardrange');
    }
};
