// const $payButtons = document.querySelectorAll(".ggn-pay-btn");
// const $payButtons = document.querySelector(".ggn-pay-btn");
// if ($payButtons) {
// 	$payButtons.addEventListener("click", e => {
// 		//e.preventDefault();
// 		console.log(e);
// 	});
// }

function ggn_click_pay_btn(e) {
	// e.preventDefault();
	e.classList.add("disabled");
	e.disabled = true;

	fetch(AJAX_URL, {
		method: "POST",
		credentials: "same-origin",
		headers: {
			//'Content-Type': 'application/json',
			//'Content-Type': 'application/x-www-form-urlencoded'
		},
		body: new URLSearchParams({
			order_id: e.dataset.id,
			order_unique_id: e.dataset.unique_id,
			action: "ggn_send_checkout_form"
		})
		// body: "action=ggn_send_checkout_form"
	})
		.then(resp => resp.json())
		.then(data => {
			e.classList.remove("disabled");
			if (data.success) {
				// console.log(data);
				let $afterFormWrap = document.createElement("div");
				e.after($afterFormWrap);
				$afterFormWrap.innerHTML = data.content;
				let $afterForm = $afterFormWrap.querySelector("form");
				$afterForm.submit();
			} else {
				$infobox.innerHTML = data.message;
				$infobox.classList.add("alert", "alert-danger");
				$infobox.style.display = "block";
			}
		})
		.catch(function (error) {
			console.log(JSON.stringify(error));
		});
}
