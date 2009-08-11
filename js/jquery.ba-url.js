/*
 * URL Utils - v1.0 - 7/10/2009
 * http://benalman.com/
 * 
 * Copyright (c) 2009 "Cowboy" Ben Alman
 * Licensed under the MIT license
 * http://benalman.com/about/license/
 */
(function($){var J,o={},x,r=true,G=false,a=Array.prototype.slice,i=document.location,C,d,b,v,t,e,A,F,n="urlInternal",I="urlExternal",w="queryString",D="fragment",E="update",f="passQueryString",u="passFragment",c="fragmentChange",h,g;function z(L){return typeof L==="string"}function k(L){return typeof L==="object"}function l(){var L=a.call(arguments),M=L.shift();return function(){return M.apply(this,L.concat(a.call(arguments)))}}function j(){return i.href.replace(/^[^#]*#?/,"")}$.urlTagAttrList=C=function(L){return $.extend(o,L)};C({a:"href",img:"src",form:"action",base:"href",script:"src",iframe:"src",link:"href"});function p(L){var M=L.nodeName;return M?o[M.toLowerCase()]:""}$.urlInternalHost=d=function(N){N=N?"(?:"+N+"\\.)?":"";var M=new RegExp("^"+N+"(.*)","i"),L="^"+i.protocol+"//"+i.hostname.replace(M,N+"$1")+(i.port?":"+i.port:"")+"/";return b(L)};$.urlInternalRegExp=b=function(L){if(L){J=z(L)?new RegExp(L,"i"):L}return J};d("www");$.isUrlInternal=v=function(L){if(!L){return x}if(J.test(L)){return r}if(/^https?:\/\//i.test(L)){return G}if(/^(?:#|[a-z\d.-]+:)/i.test(L)){return x}return r};$.isUrlExternal=t=function(L){var M=v(L);return typeof M==="boolean"?!M:M};e=function(M,L){return this.filter(":"+M+(L?"("+L+")":""))};$.fn[n]=l(e,n);$.fn[I]=l(e,I);A=function(P,O,N,M){var L=M[3]||p(O);return L?!!P($(O).attr(L)):G};$.expr[":"][n]=l(A,v);$.expr[":"][I]=l(A,t);function H(N,O,M,L){var P;if(z(M)||k(M)){return m(O,M,L,N)}else{if(k(O)){return $.param(O)}else{if(z(O)){return B(O,M,N)}else{P=N?j():i.search;return B(P,O,N)}}}}$[w]=l(H,0);$[D]=l(H,1);function K(){var L,P,O,N=a.call(arguments),M=N.shift();if(z(N[1])||k(N[1])){L=N.shift()}P=N.shift();O=N.shift();return this.each(function(){var S=$(this),Q=L||p(this),R=Q&&S.attr(Q)||"";R=H(M,R,P,O);S.attr(Q,R)})}$.fn[w]=l(K,0);$.fn[D]=l(K,1);function y(){var N=a.call(arguments),M=N.shift(),L=N.shift(),O=H(M);if($.isFunction(N[0])){O=N.shift()(O)}else{if($.isArray(N[0])){$.each(N.shift(),function(Q,P){delete O[P]})}}return H(M,L,O,N.shift())}$[f]=l(y,0);$[u]=l(y,1);function s(){var L,N=a.call(arguments),M=N.shift();if(z(N[0])){L=N.shift()}return this.each(function(){var Q=$(this),O=L||p(this),P=O&&Q.attr(O)||"";P=y.apply(this,[M,P].concat(N));Q.attr(O,P)})}$.fn[f]=l(s,0);$.fn[u]=l(s,1);function B(R,Q,N){var M,T,P,S={},O={"null":null,"true":r,"false":G},L=decodeURIComponent,U=N?/^.*[#]/:/^.*[?]|#.*$/g;R=R.replace(U,"").replace(/\+/g," ").split("&");while(R.length){M=R.shift().split("=");T=L(M[0]);if(M.length===2){P=L(M[1]);if(Q){if(P&&!isNaN(P)){P=Number(P)}else{if(P==="undefined"){P=x}else{if(O[P]!==x){P=O[P]}}}}if($.isArray(S[T])){S[T].push(P)}else{if(S[T]!==x){S[T]=[S[T],P]}else{S[T]=P}}}else{if(T){S[T]=Q?x:""}}}return S}function m(L,N,Q,M){var R,T=M?/^([^#]*)[#]?(.*)$/:/^([^#?]*)[?]?([^#]*)(#?.*)/,P=L.match(T),S=B(P[2],0,M),O=P[3]||"";if(z(N)){N=B(N,0,M)}if(Q===2){R=N}else{if(Q===1){R=$.extend({},N,S)}else{R=$.extend({},S,N)}}R=$.param(R);return P[1]+(M?"#":R||!P[1]?"?":"")+R+O}$.setFragment=F=function(M,L){var N=k(M)?H(r,M):(M||"").replace(/^#/,"");N=M?m(i.hash,"#"+N,L,1):"#";i.href=i.href.replace(/#.*$/,"")+N};$[c]=function(L){if(L===r){L=100}if(h){clearTimeout(h);h=null}if(typeof L==="number"){g=j();if($.isFunction(q)){q=q()}(function M(){var N,P=j(),O=q[D](g);if(P!==g){q[E](P,O);g=P;N=$.Event(c);N[D]=P;$(document).trigger(N)}else{if(O!==g){F(O,2)}}h=setTimeout(M,L<0?0:L)})()}};function q(){var L,M=$.browser,N={};N[E]=N[D]=function(O){return O};if(M.msie&&M.version<8){N[E]=function(Q,O){var P=L.document;if(Q!==O){P.open();P.close();P.location.hash="#"+Q}};N[D]=function(){return L.document.location.hash.replace(/^#/,"")};L=$("<iframe/>").hide().appendTo("body").get(0).contentWindow;N[E](j())}return N}})(jQuery);