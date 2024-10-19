using OpenAI.Chat;

namespace Moodler.Services;

public interface IOpenAiService
{
    ChatClient GetChatClient();
}