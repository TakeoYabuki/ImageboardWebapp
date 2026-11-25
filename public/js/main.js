const form = document.querySelector('#thread-form, #reply-form');
const message = document.getElementById('message');

if (form) {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const button = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);

        button.disabled = true;
        message.textContent = '送信中...';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(await response.text());
            }

            if (!response.redirected) {
                throw new Error('投稿結果を確認できませんでした。');
            }

            if (form.id === 'thread-form') {
                window.location.href = response.url;
                return;
            }

            // 返信一覧を更新
            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            const newReplies = doc.getElementById('replies');
            const currentReplies = document.getElementById('replies');

            if (!newReplies || !currentReplies) {
                window.location.href = response.url;
                return;
            }

            currentReplies.replaceWith(newReplies);

            history.replaceState(null, '', response.url);

            form.reset();
            message.textContent = '返信しました。';

        } catch (error) {
            message.textContent = error.message;
        } finally {
            button.disabled = false;
        }
    });
}