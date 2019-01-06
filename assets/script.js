(function() {
	'use strict';

	const catalogDiv = document.getElementById('catalogue');
	const rss = document.querySelector('a.rss');
	const spinner = document.getElementById('spinner');
	const FIELDS_PATTERN = /#(download|filedate|title|author|version|date|site|description|img)#/g;
	const defaultImg = {
		plugins: 'assets/default-icon.png',
		themes: 'assets/default-theme.png'
	};

	const imgSizes = {
		plugins: '48',
		themes: '300'
	};

	function myTemplate(imgSize) { /* #BEGIN#
				<header>
					<a href="#download#" download>#title#</a>
				</header>
				<section>
					<p><span>Auteur:</span>#author#</p>
					<p><span>Version:</span>#version#</p>
					<p><span>Date:</span>#date#</p>
					<p><span>Site:</span><a href="#site#" target="_blank">#site#</a></p>
					<div class="descr">
						<a href="#download#" download><img src="#img#" width="#IMG_SIZE#" height="#IMG_SIZE#" alt="Icon" /></a>
						<p>#description#</p>
					</div>
				</section>
				<footer>Cliquez sur le titre <span>pour télécharger le plugin</span></footer>
#END# */ return myTemplate.toString().replace(/^.*#BEGIN#/m, '').replace(/#END#.*\n.*$/m, '').replace(/#IMG_SIZE#/g, imgSize).trim() + "\n";
	}

	const xhr = new XMLHttpRequest();
	xhr.onload = function(event) {
		const datas = JSON.parse(xhr.responseText);
		if('items' in datas && 'page' in datas) {
			catalogDiv.className = datas.page;
			const pattern = myTemplate(imgSizes[datas.page]);
			if(spinner != null) { spinner.classList.remove('active'); }

			if(typeof datas.items == 'object') {
				catalogDiv.textContent = '';
				for(var i in datas.items) {
					const article = document.createElement('ARTICLE');
					article.innerHTML = pattern.replace(FIELDS_PATTERN, function(value, p1) {
						if(p1 in datas.items[i]) { return datas.items[i][p1]; }
						switch(p1) {
							case 'img' : return defaultImg[datas.page]; break;
							default: return '';
						}
					});
					catalogDiv.appendChild(article);
				}
			} else {
				catalogDiv.innerHTML = '<div class="error">Il n\'y a aucun élément</div>';
			}
		} else {
			catalogDiv.textContent = '<div class="error">Mauvais format de catalogue</div>';
		}
	};
	xhr.onerror = function(event) {
		if(spinner != null) { spinner.classList.remove('active'); }
		alert('Quelque chose s\'est mal passée');
	}

	document.getElementById('menu-ul').onclick = function(event) {
		if(event.target.tagName == 'LI') {
			event.preventDefault();
			const others = document.querySelectorAll('#menu-ul li.active');
			if(others != null) {
				for(var i=0, iMax=others.length; i<iMax; i++) {
					others[i].classList.remove('active');
				}
			}
			event.target.classList.add('active');
			if(spinner != null) { spinner.classList.add('active'); }
			if(rss != null) { rss.href = event.target.dataset.type + '-rss.xml'; }
			const url = window.location.href + 'workdir/latest/' + event.target.dataset.type + '.json';
			xhr.open('GET', url);
			xhr.send();
		}
	};
})();
