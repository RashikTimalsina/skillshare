document.addEventListener("DOMContentLoaded", () => {
  const chatMessages = document.getElementById("chat-messages");
  const chatWith = document.getElementById("chat-with");
  const chatForm = document.getElementById("chat-form");
  const receiverInput = document.getElementById("receiver_id");
  const messageInput = document.getElementById("message_text");
  const conversationsUl = document.getElementById("conversations");

  let activeReceiverId = null;

  // ================= Helper: Scroll to bottom =================
  const scrollToBottom = () => {
    chatMessages.scrollTop = chatMessages.scrollHeight;
  };

  // ================= Load messages for a conversation =================
  const loadMessages = (receiverId, receiverName) => {
    fetch(`../controllers/get_messages.php?receiver_id=${receiverId}`)
      .then((res) => res.json())
      .then((data) => {
        chatMessages.innerHTML = "";
        data.forEach((msg) => {
          const msgDiv = document.createElement("div");
          msgDiv.classList.add("message");
          // Determine sent/received
          msgDiv.classList.add(
            msg.sender_id == receiverId ? "received" : "sent"
          );
          msgDiv.textContent = msg.message_text;
          chatMessages.appendChild(msgDiv);
        });
        chatWith.textContent = receiverName;
        scrollToBottom();
      })
      .catch((err) => console.error("Error loading messages:", err));
  };

  // ================= Load conversation list dynamically =================
  const loadConversations = () => {
    fetch("../controllers/get_conversations.php")
      .then((res) => res.json())
      .then((data) => {
        conversationsUl.innerHTML = "";

        data.forEach((conv) => {
          const li = document.createElement("li");
          li.classList.add("conversation");
          li.setAttribute("data-user-id", conv.user_id);

          if (conv.unread_count > 0) li.classList.add("has-unread");

          li.innerHTML = `
            <span class="user-name">${conv.user_name}</span>
            ${
              conv.unread_count > 0
                ? `<span class="unread-count">${conv.unread_count}</span>`
                : ""
            }
          `;

          // Attach click listener
          li.addEventListener("click", () => {
            document
              .querySelectorAll(".conversation")
              .forEach((i) => i.classList.remove("active"));
            li.classList.add("active");

            const receiverId = li.getAttribute("data-user-id");
            const receiverName = conv.user_name;

            receiverInput.value = receiverId;
            activeReceiverId = receiverId;

            loadMessages(receiverId, receiverName);

            // Optional: mark messages as read
          });

          conversationsUl.appendChild(li);
        });

        // Auto-select first conversation if none active
        if (!activeReceiverId && data.length > 0) {
          const firstConv = conversationsUl.querySelector(".conversation");
          if (firstConv) firstConv.click();
        }
      })
      .catch((err) => console.error("Error loading conversations:", err));
  };

  // ================= Initial load =================
  loadConversations();

  // ================= Auto-refresh conversations every 10 seconds =================
  setInterval(loadConversations, 10000);

  // ================= Send new message =================
  chatForm.addEventListener("submit", (e) => {
    e.preventDefault();
    const messageText = messageInput.value.trim();
    const receiverId = receiverInput.value;

    if (!messageText || !receiverId) return;

    fetch("../controllers/send_message.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `receiver_id=${receiverId}&message_text=${encodeURIComponent(
        messageText
      )}`,
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          const msgDiv = document.createElement("div");
          msgDiv.classList.add("message", "sent");
          msgDiv.textContent = messageText;
          chatMessages.appendChild(msgDiv);
          messageInput.value = "";
          scrollToBottom();
        }
      })
      .catch((err) => console.error("Error sending message:", err));
  });

  // ================= Auto-refresh messages every 5 seconds =================
  setInterval(() => {
    if (activeReceiverId) {
      const receiverNameElem = document.querySelector(
        `.conversation[data-user-id="${activeReceiverId}"] .user-name`
      );
      if (receiverNameElem) {
        const receiverName = receiverNameElem.textContent;
        loadMessages(activeReceiverId, receiverName);
      }
    }
  }, 5000);
});
