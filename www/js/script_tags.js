const ul = document.querySelector(".wrapper_tag .content_tag ul"),
    input = document.querySelector("input"),
    tagNumb = document.querySelector(".details span");

let maxTags = 10,
//if tags = o then ... define else přenes přes idčko z wrapper text! zkus to přesunout do toho wraperu ať je to společně s tagy a nespaže se to při review  
    tags = ["clanek", "gravel"];
    var tagsString = document.getElementById('tags_form').getAttribute('data-tagd');
    if (tagsString == '')
    console.log(tags);
    else{
    tags = tagsString.split(",");
    }
    clanek_id = document.getElementById('tags_form').getAttribute('data-cid');
    console.log(clanek_id);

countTags();
createTag();

function countTags() {
    input.focus();
    tagNumb.innerText = maxTags - tags.length;
    document.getElementById("tag_print").innerHTML = `<h3>Nové uspořádání:#${tags}</h3>`;
    //document.getElementById("ajax-tags").innerHTML = `<h3>Uloženo:#${tags}</h3>`; - OK ale to nevypisuje hodn. z DB ale z clienta/JS
}

function createTag() {
    ul.querySelectorAll("li").forEach(li => li.remove());
    tags.slice().reverse().forEach(tag => {
        let liTag = `<li>${tag} <i class="uit uit-multiply" onclick="remove(this, '${tag}')"></i></li>`;
        ul.insertAdjacentHTML("afterbegin", liTag);
    });
    //console.log(tags);
    countTags();
}

function remove(element, tag) {
    let index = tags.indexOf(tag);
    tags = [...tags.slice(0, index), ...tags.slice(index + 1)];
    element.parentElement.remove();
    countTags();
}

function addTag(e) {
    if (e.key == "Enter") {
        let tag = e.target.value.replace(/\s+/g, ' ');
        if (tag.length > 1 && !tags.includes(tag)) {
            if (tags.length < 10) {
                tag.split(',').forEach(tag => {
                    tags.push(tag);
                    createTag();
                });
            }
        }
        e.target.value = "";
    }
}

function fetchpost() {

    var data = {
        name: tags,
        id: clanek_id,
    };
    //👇 populate the form fields with the data
    document.getElementById("tags-field").value = data.name;
    document.getElementById("id-field").value = data.clanek_id;

    var data = new FormData(document.getElementById("tags_form"));
    fetch("php-scripts/exp_tag.php", { method: "post", body: data }) //exp_tags 
        .then(res => res.text())
        .then(txt => {
            // do something when server responds
            console.log(txt);
        })
        /*.then(html => document.getElementById("ajax-tags").innerHTML = html)* /
        .then(data => {
            let tagss = data['name'];
            console.log(tagss);
            document.getElementById("ajax-tags").innerHTML = tagss;
            document.getElementById("ajax-tags").innerHTML = `${data.name}`;
        })*/
        .catch(err => console.log(err));
    // (C) PREVENT HTML FORM SUBMIT
    document.getElementById("submit_btn").style.backgroundColor="green"; // jako dobře, ale pomocí stylů!
    return false;
}

input.addEventListener("keyup", addTag);

const removeBtn = document.querySelector(".details button");
removeBtn.addEventListener("click", () => {
    tags.length = 0;
    ul.querySelectorAll("li").forEach(li => li.remove());
    countTags();
});


/*
const exportBtn = document.querySelector(".details .det button");
exportBtn.addEventListener("click", () =>{
    var src="exp.php?tag="+exp_tags;
    window.location.href=src;
});*/
