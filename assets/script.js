(function() {
	'use strict';

	const catalogDiv = document.getElementById('catalogue');
	const rss = document.querySelector('a.rss');
	const catalogJSON = document.querySelector('#repo-json');
	const catalogXML = document.querySelector('#repo-xml');
	const catalogVersion = document.querySelector('#repo-version');
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
		if(spinner != null) { spinner.classList.remove('active'); }

		if(xhr.status != 200) {
			catalogDiv.className = '';
			catalogDiv.innerHTML = '<div class="error">Erreur n°' + xhr.status + ' (<em>' + xhr.statusText +  '</em>)</div>';
			return;
		}

		const datas = JSON.parse(xhr.responseText);
		if('items' in datas && 'page' in datas) {
			catalogDiv.className = datas.page; // Taille des images
			if(rss != null) { rss.href = 'workdir/rss/' + datas.page + '.xml'; }
			var active = false;
			if(catalogJSON != null) {  catalogJSON.href = 'workdir/latest/' + datas.page + '.json'; active = true; }
			if(catalogXML != null) {
				catalogXML.href = 'workdir/xml/' + datas.page + '.xml';
				active = true;
				if(catalogVersion != null) {  catalogVersion.href = 'workdir/xml/' + datas.page + '.version'; }
			}
			const el = document.getElementById('footer');
			if(active) {
				el.classList.add('active');
			} else {
				el.classList.remove('active');
			}

			const pattern = myTemplate(imgSizes[datas.page]);

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

	function displayCatalog(itemsType) {
		const others = document.querySelectorAll('#menu-ul li.active');
		if(others != null) {
			for(var i=0, iMax=others.length; i<iMax; i++) {
				others[i].classList.remove('active');
			}
		}
		const btn = document.querySelector('#menu-ul li[data-type="' + itemsType + '"]');
		if(btn != null) { btn.classList.add('active'); }
		if(spinner != null) { spinner.classList.add('active'); }
		const url = window.location.href + 'workdir/latest/' + itemsType + '.json';
		xhr.open('GET', url);
		xhr.send();
	}

	document.getElementById('menu-ul').onclick = function(event) {
		if(event.target.tagName == 'LI' && event.target.hasAttribute('data-type')) {
			event.preventDefault();
			displayCatalog(event.target.dataset.type);
		}
	};

	displayCatalog('plugins');
})();
