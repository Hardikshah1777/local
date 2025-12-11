document.addEventListener('DOMContentLoaded', function() {
    document.getElementById("send").addEventListener("click", () => {
        const userId = Math.floor(Math.random() * 10) + 1;
        fetch("https://jsonplaceholder.typicode.com/users/" + userId, {
            method: "GET",
            headers: {
                "Content-Type": "application/json"
            },
        }).then(res => res.json())
            .then(data => {
                document.getElementById("name").value = data.name;
                document.getElementById("company").value = data.company.bs;
                document.getElementById("email").value = data.email;
                document.getElementById("phone").value = data.phone;
                document.getElementById("message").innerText = `Message: ${data.name} says hello!`;
                //document.getElementById("output").innerText = JSON.stringify(data, null, 2);
                // window.console.log(JSON.stringify(data, null, 2));
            }).catch(err => {
                document.getElementById("output").innerText = "Error: " + err;
            });
    });
    document.querySelector("form").addEventListener("submit", function (e) {
        e.preventDefault();
        if(document.getElementById("name").value) {
            const notification = new Notification("Chrome Extension dummy notification!", {
                body: "Form submited",
                icon: "/icons/logo2.png",
            });
        }
        document.querySelector("form").reset();
        document.querySelector("#message").value = '';
    });
});


