Ext.override(Ext.Element, {
	update : function(html, loadScripts, callback){
		if(typeof html == "undefined"){
			html = "";
		}
		if(loadScripts !== true){
			this.dom.innerHTML = html;
			if(typeof callback == "function"){
				callback();
			}
			return this;
		}
		var id = Ext.id();
		var dom = this.dom;

		html += '<span id="' + id + '"></span>';

		var tokenizeScripts = function(str) {
			var result = {
				scripts:[],
				other:[]
			};
			var outIdx = 0;
			var matchStart, matchEnd, token;
			var reStart = /<script.*?>/ig;
			var reEnd = /<\/script>/ig;
			while (matchStart = reStart.exec(str)) {
				token = str.substring(outIdx, reStart.lastIndex - matchStart[0].length);
				if (token.length > 0) result.other.push(token);
				reEnd.lastIndex = reStart.lastIndex;
				if (matchEnd = reEnd.exec(str)) {
					var endIndex = reEnd.lastIndex - matchEnd[0].length;
					token = str.substring(reStart.lastIndex, endIndex);
					result.scripts.push([matchStart[0], token]);
					reStart.lastIndex = reEnd.lastIndex;
					outIdx = reEnd.lastIndex;
				}
			}
			token = str.substring(outIdx);
			if (token.length > 0) result.other.push(token);
			return result;
		};

		Ext.lib.Event.onAvailable(id, function(){
			var hd = document.getElementsByTagName("head")[0];
			var srcRe = /\ssrc=([\'\"])(.*?)\1/i;
			var typeRe = /\stype=([\'\"])(.*?)\1/i;

			var tokens = tokenizeScripts(html);
			for (var i = 0; i < tokens.scripts.length; i++) {
				var start = tokens.scripts[i][0];
				var content = tokens.scripts[i][1];
				var srcMatch = start ? start.match(srcRe) : false;
				if(srcMatch && srcMatch[2]){
					var s = document.createElement("script");
					s.src = srcMatch[2];
					var typeMatch = start.match(typeRe);
					if(typeMatch && typeMatch[2]){
						s.type = typeMatch[2];
					}
					hd.appendChild(s);
				}else if(content && content.length > 0){
					if(window.execScript) {
						window.execScript(content);
					} else {
						window.eval(content);
					}
				}
			}
			var el = document.getElementById(id);
			if(el){Ext.removeNode(el);}
			if(typeof callback == "function"){
				callback();
			}
		});
		var tokens = tokenizeScripts(html);
		dom.innerHTML = tokens.other.join('');
		return this;
	}
});