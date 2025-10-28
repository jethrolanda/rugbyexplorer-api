import { createRoot } from "@wordpress/element";
import { ConfigProvider } from "antd";
import RugbyExplorer from "./RugbyExplorer";
import "./style.css";

const App = () => {
  return (
    <ConfigProvider
      theme={{
        token: {
          // Seed Token
          // colorPrimary: "#a2cd3a",
          // colorTextBase: "#D9D9D9",
          // colorBgContainer: "black",
          // Alias Token
          // colorBgContainer: "transparent",
          // miniContentHeight: "10px"
        }
      }}
    >
      <RugbyExplorer />
    </ConfigProvider>
  );
};
createRoot(document.getElementById("rugbyexplorer")).render(<App />);

// Find all DOM containers, and render order form into them.
// document.querySelectorAll(".react-calendar").forEach((domContainer) => {
//   createRoot(domContainer).render(<App />);
// });
