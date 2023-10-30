/*
 *  _____ _______         _                      _
 * |_   _|__   __|       | |                    | |
 *   | |    | |_ __   ___| |___      _____  _ __| | __  ___ ____
 *   | |    | | '_ \ / _ \ __\ \ /\ / / _ \| '__| |/ / / __|_  /
 *  _| |_   | | | | |  __/ |_ \ V  V / (_) | |  |   < | (__ / /
 * |_____|  |_|_| |_|\___|\__| \_/\_/ \___/|_|  |_|\_(_)___/___|
 *                   ___
 *                  |  _|___ ___ ___
 *                  |  _|  _| -_| -_|  LICENCE
 *                  |_| |_| |___|___|
 *
 * IT ZPRAVODAJSTVÍ  <>  PROGRAMOVÁNÍ  <>  HW A SW  <>  KOMUNITA
 *
 * Tento zdrojový kód pochází z IT sociální sítě WWW.ITNETWORK.CZ
 *
 * Můžete ho upravovat a používat jak chcete, musíte však zmínit
 * odkaz na http://www.itnetwork.cz
 */

class Diar {
	
    constructor(jazyk = "cs-CZ") {
    	const zaznamyZeStorage = localStorage.getItem("zaznamy");
        this.zaznamy = zaznamyZeStorage ? JSON.parse(zaznamyZeStorage) : [];
    	this.jazyk = jazyk;
	
    	this.nazevInput = document.getElementById("nazev");
    	this.datumInput = document.getElementById("datum");
    	this.potvrditButton = document.getElementById("potvrdit");
    	this.vypisElement = document.getElementById("seznam-ukolu");
    	this.vypisElementrun = document.getElementById("seznam-bezicich");
    	
    	this._nastavUdalosti();
    }

	_nastavUdalosti() {
		this.potvrditButton.onclick = () => { // this zůstane nyní stále this
			console.log(this.nazevInput.value.length);
			if (this.datumInput.value !== "" && this.nazevInput.value.length !== 0) {
				const zaznam = new Zaznam(this.nazevInput.value, this.datumInput.value);
				this.zaznamy.push(zaznam);
				this.ulozZaznamy();
				this.vypisZaznamy();
			} else
				alert("Musíte vyplnit datum a název!");
		};
	}
    
	seradZaznamy() {
		this.zaznamy.sort(function (zaznam1, zaznam2) {
			return (new Date(zaznam1.datum) - new Date(zaznam2.datum));
		});
	}

	vypisZaznamy() {
		this.seradZaznamy();
		this.vypisElement.innerHTML = "";

		let posledniDatum = null;
		for (const zaznam of this.zaznamy) {

			const ukol = document.createElement("div");
			ukol.className = "ukol";
						
			if (zaznam.datum !== posledniDatum) {				
				const datum = new Date(zaznam.datum).toLocaleDateString(this.jazyk, {
					weekday: "long",
					day: "numeric",
					month: "short",
					year: "numeric"
				});
				ukol.insertAdjacentHTML("beforeend", `<h3>${datum}</h3>`);
			}
			posledniDatum = zaznam.datum;

			ukol.insertAdjacentHTML("beforeend", `<h4>${zaznam.nazev}</h4>
			<p>úkol ${!zaznam.splneno ? "<span class='cervene tucne'>ne" : "<span class='zelene tucne'>"}splněn</span></p>`);
			
			ukol.insertAdjacentHTML("beforeend", `
			<p>úkol ${!zaznam.priprava ? "<span class='modre tucne'>ne" : "<span class='zelene tucne'>"}započatý</span></p>`);

			this._pridejTlacitko("Smazat", () => {
				if (confirm("Opravdu si přejete odstranit úkol?")) {
					this.zaznamy = this.zaznamy.filter(z => z !== zaznam); // Ponechá vše co není rovné proměnné zaznam
					this.ulozZaznamy();
					this.vypisZaznamy();
				}
			}, ukol);
			this._pridejTlacitko("Označit jako " + (zaznam.splneno ? "nesplněný" : "splněný"), () => { 
				zaznam.splneno = !zaznam.splneno;
				this.ulozZaznamy();
				this.vypisZaznamy();
			}, ukol);
			this._pridejTlacitko("Zařadit k " + (zaznam.priprava ? "zastaveným" : "běžícím"), () => { 
				zaznam.priprava = !zaznam.priprava;
				this.ulozZaznamy();
				this.vypisZaznamy();
			}, ukol);
						
			ukol.insertAdjacentHTML("beforeend", "</div>");
			this.vypisElement.appendChild(ukol);
		}
	}

	vypisZaznamyrun() {
		this.seradZaznamy();
		this.vypisElementrun.innerHTML = "";

		let posledniDatum = null;
		for (const zaznam of this.zaznamy) {

			const ukolr = document.createElement("div");
			ukolr.className = "ukol";
			
			if (zaznam.priprava){
				ukolr.insertAdjacentHTML("beforeend", `<h4>${zaznam.nazev}</h4>
				<p>úkol ${!zaznam.priprava ? "<span class='modre tucne'>ne" : "<span class='zelene tucne'>"}započatý</span></p>`);
				

				this._pridejTlacitko("Zařadit k " + (zaznam.priprava ? "zastaveným" : "běžícím"), () => { 
					zaznam.priprava = !zaznam.priprava;
					this.ulozZaznamy();
					this.vypisZaznamy();
				}, ukolr);
			}

			ukolr.insertAdjacentHTML("beforeend", "</div>");
			this.vypisElementrun.appendChild(ukolr);
		}
	}
	
	_pridejTlacitko(titulek, callback, element) {
		const button = document.createElement("button");
		button.onclick = callback;
		button.innerText = titulek;
		element.appendChild(button);
	}
		
	ulozZaznamy() {
		localStorage.setItem("zaznamy", JSON.stringify(this.zaznamy));
	}    
    
}

