
/* ### Amended "getCurrentSlots" method for version 1.3.01 ### */
$.fbuilder.controls['fapp'].prototype.getCurrentSlots = function (arr, d, s) {
	var me = this;
	var duration = parseFloat(me.services[s].duration);
	var html = "";
	var htmlSlots = new Array();
	var pb = 0;
	var pa = 0;
	var v = false;
	var capacity_service = me.services[s].capacity;
	if (true) {
		var compactUsedSlots = me.getCompatSlots(me.htmlUsedSlots[d])
		for (var i = 0; i < compactUsedSlots.length; i++) {
			//if (compactUsedSlots[i].quantity>=capacity_service && compactUsedSlots[i].serviceindex==s)
			if (compactUsedSlots[i].serviceindex == s) {
				compactUsedSlots[i].st = compactUsedSlots[i].h1 * 60 + compactUsedSlots[i].m1;
				compactUsedSlots[i].t = $.datepicker.parseDate("yy-mm-dd", compactUsedSlots[i].d).getTime() + compactUsedSlots[i].st * 60 * 1000;
				compactUsedSlots[i].html = "";
				var v = false;
				if (me.minDate !== "" && me.getMinDate != "") //check with the min date
				{
					var current = me.getMinDate;
					var currenttime = current.getTime() - me.tz * 60 * 60 * 1000;
					if (compactUsedSlots[i].t > currenttime) {
						v = true;
					}
				}
				else
					v = true;
				if (v) {
					if (compactUsedSlots[i].quantity >= capacity_service || compactUsedSlots[i].currentSelection)
						compactUsedSlots[i].html = '<div s="' + s + '" h1="' + compactUsedSlots[i].h1 + '" m1="' + compactUsedSlots[i].m1 + '" h2="' + compactUsedSlots[i].h2 + '" m2="' + compactUsedSlots[i].m2 + '" style="' + (!me.usedSlotsCheckbox ? "display:none" : "") + '" class="htmlUsed  ' + ((typeof compactUsedSlots[i].s !== 'undefined') ? compactUsedSlots[i].s.replace(/ /g, "").toLowerCase() + " old" : " choosen") + '"><a ' + ((typeof compactUsedSlots[i].e !== 'undefined') ? "title=\"" + compactUsedSlots[i].e + "\"" : "") + '>' + me.formatString(compactUsedSlots[i], false, me.tz) + '</a>' + ((typeof compactUsedSlots[i].e !== 'undefined') ? "<div class=\"ahbmoreinfo\">" + compactUsedSlots[i].e + "</div>" : "") + '</div>';
					compactUsedSlots[i].availableslot = false;
					htmlSlots[htmlSlots.length] = compactUsedSlots[i];
				}
			}
		}
	}

	if ((typeof specialPadding === 'undefined')) {
		pb = me.pb;
		pa = me.pa;
	}
	for (var i = 0; i < arr.length; i++) {
		st = arr[i].t1 || (arr[i].h1 * 60 + arr[i].m1 * 1);
		et = arr[i].t2 || (arr[i].h2 * 60 + arr[i].m2 * 1);
		if (st >= et)
			et += 24 * 60;
		st += me.pb;
		while (st + duration + me.pa <= et && st < 24 * 60) {
			html = "<div class=\"availableslot\"><a  s=\"" + s + "\"  href=\"\" d=\"" + arr[i].day + "\" h1=\"" + Math.floor((st) / 60) + "\" m1=\"" + ((st) % 60) + "\" h2=\"" + Math.floor((st + duration) / 60) + "\" m2=\"" + ((st + duration) % 60) + "\">" + me.formatString({ st: st, et: st + duration }, false, me.tz) + ((typeof cp_hourbk_cmpublic !== 'undefined') ? "<span class=\"ahb_slot_availability\"><span class=\"p\">ahbslotavailabilityP</span><span class=\"t\">ahbslotavailabilityT</span></span>" : "") + "</a></div>";
			htmlSlots[htmlSlots.length] = { availableslot: true, st: st, serviceindex: s, h1: Math.floor((st) / 60), m1: ((st) % 60), h2: Math.floor((st + duration) / 60), m2: ((st + duration) % 60), html: html, t: $.datepicker.parseDate("yy-mm-dd", arr[i].day).getTime() + st * 60 * 1000 };
			if (!me.bSlotsCheckbox)
				st += me.bduration;
			else
				st += me.bduration + pa + pb;
		}
	}

	htmlSlots.sort(function (a, b) {
		return a.t - b.t;
	});

	var slotQty = {};
	htmlSlots.filter(function (i) {
		return !i.availableslot;
	}).forEach(function (s) {
		if (!slotQty.hasOwnProperty(s.t)) {
			slotQty[s.t] = 0;
		}
		slotQty[s.t] += s.quantity;
	});

	htmlSlots.filter(function (i) {
		return i.availableslot;
	}).forEach(function (x) {
		x.html = x.html.replace("ahbslotavailabilityP", (capacity_service - (slotQty[x.t] ?? 0)));
	});

	//remove duplicates
	htmlSlots = htmlSlots.reduce(function (field, e1) {
		var matches = field.filter(function (e2) { return e1.html == e2.html });
		if (matches.length == 0) {
			field.push(e1);
		} return field;
	}, []);
	htmlSlots = htmlSlots.reduce(function (field, e1) {
		var matches = field.filter(function (e2) { return e1.t == e2.t });
		if (matches.length == 0) {
			field.push(e1);
		}
		else {
			for (var i = 0; i < field.length; i++)
				if (field[i].t == e1.t && !field[i].availableslot && e1.availableslot) {
					field[i] = e1;
					break;
				}
		}
		return field;
	}, []);
	me.usedSlots[d] = me.usedSlots[d] || [];
	if (me.usedSlots[d].length > 0 && htmlSlots.length > 0)
		for (var i = 0; i < me.usedSlots[d].length; i++)
			for (var j = 0; j < htmlSlots.length; j++)
				if (htmlSlots[j].serviceindex == me.usedSlots[d][i].serviceindex && htmlSlots[j].h1 == me.usedSlots[d][i].h1 && htmlSlots[j].m1 == me.usedSlots[d][i].m1 && htmlSlots[j].h2 == me.usedSlots[d][i].h2 && htmlSlots[j].m2 == me.usedSlots[d][i].m2) {
					if (htmlSlots[j].html.indexOf("currentSelection") == -1) htmlSlots[j].html = htmlSlots[j].html.replace("htmlUsed", "htmlUsed currentSelection");
					if (htmlSlots[j].html.indexOf("currentSelection") == -1) htmlSlots[j].html = htmlSlots[j].html.replace("availableslot", "availableslot currentSelection");
				}
	return htmlSlots;
}
