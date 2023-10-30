const sortableList = document.querySelector(".sortable-list");
const items = sortableList.querySelectorAll(".item");

items.forEach(item => {
    item.addEventListener("dragstart", () => {
        // Adding dragging class to item after a delay
        setTimeout(() => item.classList.add("dragging"), 0);
 
        /*const det = document.querySelector(".details");
        var imgsrc = det.getElementById("img").src;
        var imgsrc = imgsrc.split("/").reverse()[0];
        console.log(imgsrc);*/

        //alert(imgsrc);
    });
    // Removing dragging class from item on dragend event
    item.addEventListener("dragend", () => item.classList.remove("dragging"));
});

const initSortableList = (e) => {
    e.preventDefault();
    const draggingItem = document.querySelector(".dragging");
    // Getting all items except currently dragging and making array of them
    let siblings = [...sortableList.querySelectorAll(".item:not(.dragging)")];

    // Finding the sibling after which the dragging item should be placed
    let nextSibling = siblings.find(sibling => {
        return e.clientY <= sibling.offsetTop + sibling.offsetHeight / 2;
    });

    //lconsole.log(nextSibling);
    // Inserting the dragging item before the found sibling
    sortableList.insertBefore(draggingItem, nextSibling);
    
    // nepoda5en8 snaha sledovat /atribut/ jméno souboru na jehoš konci je /číslo/ pořadí
    /*var imgsrc = document.getElementById("img").src;
    var imgsrc = imgsrc.split("/").reverse()[0];*/
    // až najdu hezčí formu nalezení pole row smaž celou sekci imgsrc!!!
    
    const list = sortableList.querySelectorAll(".item .details");
    let row = [];let i = 0;
    list.forEach(piece => {
        //console.log(piece.classList[1][2]);
        row[i] =piece.classList[1][2];
        i++;    
    });
    //console.log(row);
}
sortableList.addEventListener("dragover", initSortableList);
sortableList.addEventListener("dragenter", e => e.preventDefault());