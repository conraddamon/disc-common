/**
 * Polyfill for Object.values()
 */
if (!Object.values) {
    Object.values = function(obj) {
	return Object.keys(obj).map(key => obj[key]);
    }
}

// map division codes to full names
const DIV_NAME = {
    'O': 'Open',
    'OM': 'Open Master',
    'OGM': 'Open Grand Master',
    'OSGM': 'Open Senior Grand Master',
    'OL': 'Open Legend',
    'OJ': 'Open Junior',
    'W': 'Women',
    'WM': 'Women Master',
    'WGM': 'Women Grand Master',
    'WSGM': 'Women Senior Grand Master',
    'WL': 'Women Legend',
    'WJ': 'Women Junior',
    'MX': 'Mixed'
};

// order to present divisions in
const DIV_ORDER = [ 'O', 'OM', 'OGM', 'OSGM', 'OL', 'OJ', 'W', 'WM', 'WGM', 'WSGM', 'WL', 'WJ' ];

/**
 * Removes an item from an array.
 *
 * @param {Array}   array
 * @param {mixed}   a member of the array
 */
function removeItem(array, item) {

    array = array || [];
    let i = array.indexOf(item);
    if (i !== -1) {
	array.splice(i, 1);
    }
}

/**
 * Parses the URL's query string into key/value pairs. A key without a value gets a value of true.
 */
function parseQueryString() {

    var qs = {},
	s = window.location.search,
	pairs = s ? s.substr(1).split('&') : [];

    pairs.forEach(function(pair) {
	    var args = pair.split('=');
	    qs[args[0]] = args[1] || true;
	});

    return qs;
}

/**
 *  Displays any dollar amount with two decimals and a dollar sign.
 *
 * @param {int|Number|String} amt       amount to display
 * @param {Boolean}           showZero  if true, zero is displayed numerically rather than as a space
 */
function formatMoney(amt, showZero) {

    amt = Number(amt);
    return amt > 0 || showZero ? '$' + amt.toFixed(2) : ' ';
}

function fromMysqlDate(date) {

    var parts = date.split('-');
    return new Date(parts[0], parts[1] - 1, parts[2].substr(0, 2));
}

function toMysqlDate(date) {

    let month = date.getMonth() + 1,
	day = date.getDate(),
	year = date.getYear() + 1900;

    month = month < 10 ? '0' + month : month;
    day = day < 10 ? '0' + day : day;

    return [year, month, day].join('-');
}

/**
 * Performs an operation on the back end.
 *
 * @param {string}   op        operation to perform     
 * @param {function} callback  function to call when response comes
 * @param {object}   args      optional arguments
 *
 * @deprecated in favor of sendRequest
 */
function sendMessage(op, callback, args) {

    var m = location.pathname.match(/^\/(\w+)/),
	app = m && m[1];

    args = args || {};
    args.op = op;

    $.ajax({
        url: "/data/" + app + ".php",
	data: args,
	success: function(result) {
	    if (callback && result) {
		callback(JSON.parse(result));
	    }
	}
    });
}

/**
 * Sends a request to the server and optionally calls a callback with the results. If a callback
 * is not provided, this function returns a jQuery Deferred object which can be handed to various
 * promise-like functions.
 *
 * @param {string}   op        operation to perform     
 * @param {object}   args      (optional) arguments for op
 * @param {function} callback  (optional) function to call when response comes
 *
 * @return a jQuery Deferred object
 */
function sendRequest(op, args, callback) {

    var m = location.pathname.match(/^\/(\w+)/),
	app = m && m[1];

    if (location.hostname === 'doubledisccourt.com') {
	app = 'ddc';
    }
    else if (location.hostname === 'overalldisc.com') {
	app = 'overall';
    }

    args = args || {};
    args.op = op;

    var success;
    if (typeof callback == 'function') {
	success = function(result) {
	    callback(result ? JSON.parse(result) : null);
	}
    }

    return $.get("/data/" + app + ".php", args, success);
}

/**
 * Sends a set of requests and runs their callbacks with their responses. The requests are not
 * inter-dependent.
 *
 * @param {Array} requests     a list of Deferred objects (returned by sendRequest)
 * @param {Array} callbacks    (optional) ordered list of callbacks for the requests
 *
 * @return a jQuery Deferred object
 */
function sendRequests(requests, callbacks) {

    callbacks = callbacks || [];
    return $.when(...requests).done(function(...results) {
	    results.forEach(function(result, index) {
		    if (typeof callbacks[index] == 'function') {
			var res = $.isArray(result) ? result[0] : result;
			callbacks[index](res != null ? JSON.parse(res) : null);
		    }
		});
	});
}

/**
 * Capitalizes each word in the given string.
 *
 * @param {string} str    a string
 */
function capitalize(str) {

    str = str || '';
    var words = str.split(/\s+/);
    return words.map(function(w) {
	    return w.charAt(0).toUpperCase() + w.substr(1).toLowerCase();
	}).join(' ');
}

/**
 * Capitalizes a name if it appears to be lowercase.
 *
 * @param {string} str    a string
 */
function capitalizeName(str) {

    str = str || '';

    // do nothing if name is already capitalized; don't mess with McBain, etc.
    if (/^[A-Z].*[a-z]/.test(str)) {
	return str;
    }

    var name = capitalize(str),
	parts = name.split(' '),
	last = parts[parts.length - 1];

    if (last === 'Ii' || last === 'Iii' || last === "Iv") {
	name = name.replace(last, last.toUpperCase());
    }
    
    return name;
}

/**
 * Returns the result of comparing two names. Last name is the primary key. Handles team names if it sees a slash.
 * The first team member is used for comparison.
 *
 * NOTE: localeCompare() seems to be inconsistent on Chrome. Works fine on Firefox.
 *
 * @param {string} a    a name
 * @param {string} b    a name
 */
function compareNames(a, b) {

    if (a == null || b == null) {
	return a == b ? 0 : a == null ? -1 : 1;
    }

    if (a.indexOf('/') !== -1) {
	a = a.split(/\s*\/\s*/)[0];
    }
    if (b.indexOf('/') !== -1) {
	b = b.split(/\s*\/\s*/)[0];
    }

    var aIdx = getNameSplitIndex(a),
	aLast = aIdx !== -1 ? a.substr(aIdx + 1) : a,
	aFirst = aIdx !== -1 ? a.substr(0, aIdx) : '',
	bIdx = getNameSplitIndex(b),
	bLast = bIdx !== -1 ? b.substr(bIdx + 1) : b,
	bFirst = bIdx !== -1 ? b.substr(0, bIdx) : '';
    
    return aLast !== bLast ? aLast.localeCompare(bLast) : aFirst.localeCompare(bFirst);
}

function getNameSplitIndex(name) {
    
    name = name.toLowerCase();
    var idx = regexIndexOf(name, /\s+(van|von|de|da)\s+/);
    if (idx !== -1) {
	return idx;
    }

    var m = name.match(/(,? jr| sr| ii| iii)$/),
	start = m ? name.length - m[1].length : name.length;

    return name.lastIndexOf(' ', start - 1);
}

// http://stackoverflow.com/questions/273789/is-there-a-version-of-javascripts-string-indexof-that-allows-for-regular-expr
function regexIndexOf(str, regex, startpos) {

    var idx = str.substring(startpos || 0).search(regex);
    return idx >= 0 ? idx + (startpos || 0) : idx;
}

/**
 * Saves name data in the global scope, keyed by ID. Also creates a map from name to ID.
 *
 * @param {Array}  data    a list of objects with at least a name and an ID
 * @param {string} type    namespace to save the data in
 */
function saveNames(data, type='person') {

    var store = window[type + 'Data'] = window[type + 'Data'] || {}, // global storage for person data
        map = window[type + 'Id'] = window[type + 'Id'] = {};       // map name to ID

    // store data
    for (var i = 0; i < data.length; i++) {
        var p = data[i];
        store[p.id] = p;
        map[p.name] = p.id;
    }
}

/**
 * Takes a list of objects that have names and returns a list of names. Intended for autocomplete.
 *
 * @param {Array}    data       a list of objects with names
 * @param {function} mapFunc    (optional) function for extracting the name from an object
 */
function getNameList(data, mapFunc) {

    if (!data) {
	return [];
    }

    data = typeof data === 'object' ? Object.values(data) : data;
    var acList = data.map(mapFunc || function(obj) {
	return obj.name;
    });

    acList.sort(compareNames);

    return acList;
}

/**
 * Adds a jQuery Autocomplete widget to the given player name entry field.
 *
 * @param {Array}    nameList       list of names to match against
 * @param {function} matchFunc      (optional) custom matching function
 * @param {function} rejectFunc     (optional) function to exclude names from match list
 * @param {function} selectFunc     (optional) function to run when a name has been selected
 * @param {string}   playerFieldId  ID of player text field
 * @param {string}   scoreFieldId   (optional) ID of score field, which gets focus after selection
 */
function addNameAutocomplete(nameList,  matchFunc, rejectFunc, selectFunc, playerFieldId='player', scoreFieldId='score') {

    // set up the jQuery autocompleter
    $('#' + playerFieldId).autocomplete({
	    autoFocus: true, // auto-select first match in list
	    source: function(request, response) {
		// set up custom matching function that matches beginning of first or last name
		var matches = $.map(nameList, function(acItem) {
			if (!acItem) {
			    return null;
			}
			matchFunc = matchFunc || matchName;
			var matches = matchFunc(request.term, acItem);
			if (matches && !(rejectFunc && rejectFunc(acItem))) {
			    return acItem;
			}
		    });
		response(matches);
	    },
	    // move focus along after user selects a name
	    select: function(e, ui) {
		if (selectFunc) {
		    selectFunc(e, ui);
		}
		var scoreField = $('#' + scoreFieldId);
		if (scoreField) {
		    setTimeout(function() {
			    scoreField.focus();
			}, 0);
		}
	    }
	});
}

/**
 * Returns true if the given string matches the given name.
 *
 * @param {string} str    match string
 * @param {string} name   a name
 */
function matchName(str, name) {

    str = str.toLowerCase();
    name = name.toLowerCase();

    var idx = getNameSplitIndex(name),
	first = idx !== -1 ? name.substr(0, idx) : '',
	last = idx !== -1 ? name.substr(idx + 1) : name;

    return (name.indexOf(str) === 0 || first.indexOf(str) === 0 || last.indexOf(str) === 0);
}

/**
 * Convert an array to a lookup hash to test for the presence of its elements.
 *
 * @param {Array} list    an array
 */
function toLookupHash(list) {

    var hash = {};
    list.forEach(x => hash[x] = true);

    return hash;
}

function uniquify(list) {

    return list.filter(function(result, idx, self) {
	    return idx === self.indexOf(result); // remove duplicates                                              
	});
}

function mergeArray(target, source) {

    Array.prototype.push.apply(target, source);
}

function showLogin() {

	$('#loginDialog').dialog({
		autoOpen: true,
		modal: true,
		width: '35rem',
		    //		position: { my: 'center', at: 'center', of: '#addPlayer' },
	        draggable: false,
		resizable: false
	    });
    $('#targetUrl').val('testing');

    $('#loginButton').click(doLogin);
}

function doLogin(targetUrl) {

    $('#loginForm').submit();
}

function getLinkBar(links, useAnchors) {

    let html = '<div class="pageLinks">';
    if (useAnchors) {
	links.forEach(link => html += '[ <a class="pageLink" href="#' + link.id + '">' + link.text + '</a> ]');
    }
    else {
	links.forEach(link => html += '[ <span class="pageLink" id="' + link.id + '">' + link.text + '</span> ]');
    }
    html += '</div>';

    return html;
}

/**
 * Handles a click (from a bar of links) by showing the target page.
 *
 * @param {Event} e    browser event
 */
function pageLinkClicked(e) {

    showPage(e.target.id);
}
