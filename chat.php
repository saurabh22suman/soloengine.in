<?php
/**
 * Chat Widget
 * 
 * Frontend chat interface component for portfolio site
 * 
 * SECURITY: Implements CSRF protection and input validation
 */

// Start session for CSRF protection
session_start();

// Generate CSRF token if not exists
if (!isset($_SESSION['chat_csrf_token'])) {
    $_SESSION['chat_csrf_token'] = bin2hex(random_bytes(32));
}

// Generate unique session ID for this user if not exists
if (!isset($_SESSION['chat_session_id'])) {
    $_SESSION['chat_session_id'] = bin2hex(random_bytes(16));
}

// Create page header
$pageTitle = "Portfolio Chat";
require_once 'header.php';
?>

<div class="container mt-4 mb-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2 class="h5 mb-0">
                        <i class="fas fa-robot me-2"></i> Portfolio Chat Assistant
                    </h2>
                </div>
                
                <div class="card-body chat-container">
                    <!-- Welcome message -->
                    <div class="chat-messages" id="chatMessages">
                        <div class="chat-message system">
                            <div class="message-content">
                                <p>👋 Hello! I'm your friendly portfolio assistant. Ask me anything about my projects, skills, or experience!</p>
                            </div>
                            <div class="message-time">Just now</div>
                        </div>
                    </div>
                    
                    <!-- Sources/Attribution area -->
                    <div class="chat-sources small text-muted mt-2 mb-3 d-none" id="chatSources">
                        <p class="mb-1 fst-italic">Sources:</p>
                        <ul class="mb-0 ps-3" id="sourcesList"></ul>
                    </div>
                    
                    <!-- Input form -->
                    <div class="chat-input">
                        <form id="chatForm">
                            <div class="input-group">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['chat_csrf_token']) ?>">
                                <input type="text" class="form-control" id="userMessage" placeholder="Type your message here..." autocomplete="off" required>
                                <button class="btn btn-primary" type="submit" id="sendBtn">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                        <div class="text-center mt-2">
                            <small class="text-muted">Using RAG technology with Hugging Face AI</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.chat-container {
    display: flex;
    flex-direction: column;
    height: 500px;
}

.chat-messages {
    flex-grow: 1;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 1rem;
    padding-right: 0.5rem;
}

.chat-message {
    display: flex;
    flex-direction: column;
    max-width: 80%;
    margin-bottom: 0.5rem;
}

.chat-message.user {
    align-self: flex-end;
}

.chat-message.system {
    align-self: flex-start;
}

.message-content {
    padding: 0.75rem 1rem;
    border-radius: 1rem;
    word-break: break-word;
}

.user .message-content {
    background-color: var(--bs-primary);
    color: white;
    border-top-right-radius: 0.25rem;
}

.system .message-content {
    background-color: var(--bs-light);
    color: var(--bs-dark);
    border-top-left-radius: 0.25rem;
}

.message-time {
    font-size: 0.75rem;
    color: var(--bs-secondary);
    margin-top: 0.25rem;
    align-self: flex-end;
}

.user .message-time {
    align-self: flex-end;
}

.system .message-time {
    align-self: flex-start;
}

.chat-input {
    margin-top: auto;
}

.chat-thinking {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem;
    background-color: var(--bs-light);
    border-radius: 1rem;
    width: fit-content;
    margin-bottom: 0.5rem;
}

.chat-thinking .dot {
    width: 8px;
    height: 8px;
    background-color: var(--bs-secondary);
    border-radius: 50%;
    animation: pulse 1.5s infinite;
    opacity: 0.7;
}

.chat-thinking .dot:nth-child(2) {
    animation-delay: 0.3s;
}

.chat-thinking .dot:nth-child(3) {
    animation-delay: 0.6s;
}

@keyframes pulse {
    0%, 100% { opacity: 0.3; transform: scale(0.8); }
    50% { opacity: 1; transform: scale(1); }
}

/* Ensure sources don't affect layout while hidden */
.chat-sources.d-none {
    display: none !important;
}

/* Smooth reveal for sources */
.chat-sources {
    transition: all 0.3s ease;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatForm = document.getElementById('chatForm');
    const userMessageInput = document.getElementById('userMessage');
    const chatMessages = document.getElementById('chatMessages');
    const sendBtn = document.getElementById('sendBtn');
    const chatSources = document.getElementById('chatSources');
    const sourcesList = document.getElementById('sourcesList');
    
    // Scroll to bottom of chat
    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Format current time
    function formatTime() {
        const now = new Date();
        return now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    
    // Add user message to the chat
    function addUserMessage(message) {
        const messageEl = document.createElement('div');
        messageEl.className = 'chat-message user';
        messageEl.innerHTML = `
            <div class="message-content">${escapeHtml(message)}</div>
            <div class="message-time">${formatTime()}</div>
        `;
        chatMessages.appendChild(messageEl);
        scrollToBottom();
    }
    
    // Add system message to the chat
    function addSystemMessage(message) {
        const messageEl = document.createElement('div');
        messageEl.className = 'chat-message system';
        messageEl.innerHTML = `
            <div class="message-content">${formatMessage(message)}</div>
            <div class="message-time">${formatTime()}</div>
        `;
        chatMessages.appendChild(messageEl);
        scrollToBottom();
    }
    
    // Add thinking indicator
    function addThinkingIndicator() {
        const thinkingEl = document.createElement('div');
        thinkingEl.className = 'chat-thinking system';
        thinkingEl.id = 'thinkingIndicator';
        thinkingEl.innerHTML = `
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
        `;
        chatMessages.appendChild(thinkingEl);
        scrollToBottom();
    }
    
    // Remove thinking indicator
    function removeThinkingIndicator() {
        const thinkingEl = document.getElementById('thinkingIndicator');
        if (thinkingEl) {
            thinkingEl.remove();
        }
    }
    
    // Update sources
    function updateSources(sources) {
        sourcesList.innerHTML = '';
        
        if (sources && sources.length > 0) {
            sources.forEach(source => {
                const li = document.createElement('li');
                li.textContent = `${source.title}`;
                sourcesList.appendChild(li);
            });
            chatSources.classList.remove('d-none');
        } else {
            chatSources.classList.add('d-none');
        }
    }
    
    // Format message (convert URLs, line breaks etc.)
    function formatMessage(text) {
        // Convert line breaks to <br>
        text = text.replace(/\n/g, '<br>');
        
        // Convert URLs to clickable links
        const urlRegex = /(https?:\/\/[^\s]+)/g;
        text = text.replace(urlRegex, url => `<a href="${url}" target="_blank" rel="noopener noreferrer">${url}</a>`);
        
        return text;
    }
    
    // Escape HTML special characters
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Handle form submission
    chatForm.addEventListener('submit', function(event) {
        event.preventDefault();
        
        const userMessage = userMessageInput.value.trim();
        if (!userMessage) return;
        
        // Add user message to chat
        addUserMessage(userMessage);
        
        // Clear input
        userMessageInput.value = '';
        
        // Disable input and button
        userMessageInput.disabled = true;
        sendBtn.disabled = true;
        
        // Show thinking indicator
        addThinkingIndicator();
        
        // Reset sources
        updateSources([]);
        
        // Send request to server
        fetch('rag_chat_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                message: userMessage,
                csrf_token: '<?= htmlspecialchars($_SESSION['chat_csrf_token']) ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            // Remove thinking indicator
            removeThinkingIndicator();
            
            // Add system response
            if (data.status === 'success') {
                addSystemMessage(data.message);
                
                // Update sources if available
                if (data.sources && data.sources.length > 0) {
                    updateSources(data.sources);
                }
            } else {
                // Handle error
                addSystemMessage('Sorry, I encountered an error. Please try again later.');
                console.error('Error:', data.message);
            }
        })
        .catch(error => {
            // Remove thinking indicator
            removeThinkingIndicator();
            
            // Add error message
            addSystemMessage('Sorry, there was an error communicating with the server. Please try again.');
            console.error('Error:', error);
        })
        .finally(() => {
            // Re-enable input and button
            userMessageInput.disabled = false;
            sendBtn.disabled = false;
            userMessageInput.focus();
        });
    });
    
    // Focus on input field on page load
    userMessageInput.focus();
    
    // Scroll to bottom initially
    scrollToBottom();
});
</script>

<?php require_once 'includes/footer.php'; ?>
