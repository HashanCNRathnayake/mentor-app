import React, { useState } from "react";
import {
  View,
  Text,
  Button,
  StyleSheet,
  ScrollView
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

export default function App() {

  const [responseText, setResponseText] = useState("No response yet");

  const sendMessage = async () => {

    console.log("🔘 Button clicked");

    const requestBody = {
      conversationId: "",
      message: "start",
      email: "educlaasapps@learning.educlaas.com",
      name: "educlaas apps developer 01",
      role: "learner"
    };

    console.log("📦 Request Body:", requestBody);
    console.log("📤 Sending request to API...");

    try {

      const response = await fetch(
        "http://192.168.1.100/projects/claas2saas/mentor-app/copilot-api/chat.php",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json"
          },
          body: JSON.stringify(requestBody)
        }
      );

      console.log("📥 Response received");
      console.log("📊 Response status:", response.status);

      const data = await response.text();

      console.log("📄 Response data:");
      console.log(data);

      setResponseText("Reply: " + data);

    } catch (error) {

      console.log("❌ Error occurred:");
      console.log(error);

      setResponseText("Error: " + error);
    }
  };

  return (
    
    <SafeAreaView style={styles.container}>

      <View style={styles.buttonContainer}>
        <Button title="Send Request" onPress={sendMessage} />
      </View>

      <ScrollView style={styles.responseBox}>
        <Text>{responseText}</Text>
      </ScrollView>

    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    padding: 20,
    backgroundColor: "#e4dfdf"
  },
  buttonContainer: {
    marginBottom: 20
  },
  responseBox: {
    color: "#333",
    backgroundColor: "#868484",
    padding: 15,
    borderRadius: 5
  }
});