function delete_modal ($id) {
  let url = document.getElementById("user-delete"); 
  newUrl = url.href.slice(0, -1) + $id;
  url.setAttribute('href', newUrl);
}