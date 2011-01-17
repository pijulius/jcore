/**
* Keyz: a jQuery Plugin
* @author: Kathryn Reeve (BinaryKitten)
* @url: http://www.BinaryKitten.me.uk
* @documentation: http://www.BinaryKitten.me.uk
* @published: 22nd February 2010
* @version 1.0.2
*/
if(typeof jQuery != 'undefined'){
	jQuery(function($) {
		$.fn.extend({
			keyz: function(keyDown,keyPress,keyUp) {
				var keyList = {
					up: 		{},
					down: 	{},
					press: 	{},
					chain: 	{}
				};

				var getKeys = function(options) {
					var accumulator = {}
					for (var propertyName in options) {
						var lstKeys = $.trim(propertyName).toLowerCase().replace('-','').split(' ');
						for(var i=0,cnt = lstKeys.length;i<cnt;i++) {
							var key = lstKeys[i];
							if (isNaN(key)) {
								if (typeof $.fn.keyz.keymap[key] != 'undefined') {
									var mapItem = $.fn.keyz.keymap[key];
									if ($.isArray(mapItem)) {
										for(var i =0,l = mapItem.length;i<l;i++) {
											accumulator[mapItem[i]] = options[propertyName];
										}
									}
									else {
										accumulator[mapItem] = options[propertyName];
									}
								}
							}
							else {
								accumulator[key] = options[propertyName];
							}
						}
					}
					return accumulator;
				}
				var keyEvent = function(e,code,keyArray) {
					if (typeof keyArray[code] != 'undefined') {
						var item = keyArray[code];
						if (false === item) {
							e.preventDefault();
						}
						else {
							if ($.isFunction(item)) {
								var ret = item.call(this,e.ctrlKey,  e.shiftKey, e.altKey,e);
								if (false === ret) {
									e.preventDefault();
								}
							}
						}
					}
					else {
						if ((typeof keyArray['default'] != 'undefined') && $.isFunction(keyArray['default'])) {
							keyArray['default'].call(this,code,e.ctrlKey,  e.shiftKey, e.altKey,e);
						}
					}
				}

				keyList.down = getKeys(keyDown);
				this.bind('keydown.keyz', function(e){
					keyEvent.call(this,e,e.which,keyList.down);
					//keyChain.call(this,e,keyList.chain);
				});
				if ((typeof keyUp != 'undefined') && (typeof keyUp == 'object')) {
					keyList.up = getKeys(keyUp);
					this.bind('keyup.keyz', function(e){
						keyEvent.call(this,e,e.which,keyList.up);
					});
				}
				if ((typeof keyPress != 'undefined') && (typeof keyPress == 'object')) {
					keyList.press = getKeys(keyDown);
					this.bind('keypress.keyz', function(e){
						keyEvent.call(this,e,e.which,keyList.press);
					});
				}
				return this
			}
		});
		$.fn.keyz.keymap  = {
			"enter": 		13,
			"return": 		13,
			"esc":			27,
			"escape": 		27,
			"numerics":	[48,49,50,51,52,53,54,55,56,57],
			"upper": 			[65,66,67,68, 69,70,71,72,73,74,75,76,77,78,79,80,81,82,83,84,85,86,87,88,89,90],
			"lower":			[97,98,99,100,101],
			"alphanumeric":	[65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,81,82,83,84,85,86,87,88,89,90,48,49,50,51,52,53,54,55,56,57],
			"tab": 				9,
			"shift": 				16,
			"alt": 				17,
			"ctrl": 				18,
			"f1":			112,
			"f2":			113,
			"f3":			114,
			"f4":			115,
			"f5":			116,
			"f6":			117,
			"f7":			118,
			"f8":			119,
			"f9":			120,
			"f10":		121,
			"f11":		122,
			"f12":		123,
			"caps":		20,
			"capslock": 20,
			"numlock": 144,
			"winflag":	91,
			"winkey":	91,
			"windows": 91,
			"scrolllock": 145,
			"left": 37,
			"up": 38,
			"right": 39,
			"down": 40,
			"volumeup":175,
			"volumedown": 174,
			"menu": 93,
			"contextmenu": 93,
			"backspace": 8,
			"pause": 19,
			"break": 19,
			"pausebreak": 19,
			"pageup": 33,
			"pagedown": 34,
			"end": 35,
			"home": 36,
			"insert": 45,
			"del": 46,
			"delete": 46,
			"numpad0": 96,
			"numpad1": 97,
			"numpad2": 98,
			"numpad3": 99,
			"numpad4": 100,
			"numpad5": 101,
			"numpad6": 102,
			"numpad7": 103,
			"numpad8": 104,
			"numpad9": 105,
			"*": 106,
			"multiply": 106,
			"+": 107,
			"add": 107,
			"-": 109,
			"subtract": 109,
			".": [110,190],
			"fullstop": [110,190],
			"decimal": [110,190],
			"/": [111,191],
			"divide": [111,191],
			";": 59,
			"semicolon": 59
		}
	});
}

